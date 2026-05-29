<?php
namespace console\controllers;

use common\models\Invoice;
use common\models\InvoiceHasTemplate;
use common\models\Item;
use common\models\PaymentMetadata;
use common\models\Subscription;
use common\models\Template;
use kartik\mpdf\Pdf;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\db\ActiveQuery;
use yii\db\Expression;

class PaymentController extends Controller
{
	/**
	 * @var \DateTime The current date and time instance used for this process operations.
	 */
	public static $currentDate;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		self::$currentDate = new \DateTime();
	}

	/**
	 * Run the payment check.
	 *
	 * @return int
	 */
	public function actionRun()
	{
		try {
			$subscriptions = self::getDueSubscriptions();
			$count = 0;

			foreach ($subscriptions as $subscription) {
				$endAt = new \DateTime($subscription->end_at);
				// Generate the unpaid invoice, then send it to email
				if (!($invoice = self::saveInvoice($subscription))) {
					continue;
				}
				// Check the subscription, suspend it or send payment confirmation to email
				if (self::$currentDate >= $endAt || $endAt->format('Y-m-d H:i') == self::$currentDate->format('Y-m-d H:i')) {
					self::checkSubscription($subscription, $invoice);
				}
				$count++;
			}

			$this->stdout("[" . date("Y-m-d H:i:s") . "] Processed {$count} subscription(s).");
			return ExitCode::OK;
		} catch (\Exception $e) {
			$this->stderr($e->getMessage());
			return ExitCode::UNSPECIFIED_ERROR;
		} catch (\Throwable $e) {
			$this->stderr($e->getMessage());
			return ExitCode::UNSPECIFIED_ERROR;
		}
	}

	/**
	 * Gets the subscriptions that needs to be paid.
	 *
	 * @return array|\yii\db\ActiveRecord[]|Subscription[]
	 */
	protected static function getDueSubscriptions()
	{
		$currentDate = self::$currentDate->format('Y-m-d H:i:s');
		$invoiceIssuanceBeforePeriod = Yii::$app->settings->get('invoiceIssuanceBeforePeriod', 'payment');

		$paidInvoicesQuery = Invoice::find()
			->alias('i')
			->select(['itm.item_id'])
			->joinWith([
				'items itm' => function (ActiveQuery $query) {
					$query->andWhere(['itm.type' => Item::TYPE_SUBSCRIPTION]);
				},
			], false)
			->andWhere(new Expression("TIMESTAMPDIFF(DAY, [[i.issued_at]], '{$currentDate}') <= '{$invoiceIssuanceBeforePeriod}'"))
			->andWhere([
				'i.status' => Invoice::STATUS_PAID,
				'i.deleted' => Invoice::NO,
			]);

		$subscriptionsQuery = Subscription::find()
			->alias('sub')
			->andWhere([
				'AND',
				['IS', 'sub.parent_id', null],
				['!=', 'sub.type', Subscription::TYPE_FREE],
				['=', 'sub.status', Subscription::STATUS_ACTIVE],
			])
			->andWhere(new Expression("TIMESTAMPDIFF(DAY, '{$currentDate}', [[sub.end_at]]) <= '{$invoiceIssuanceBeforePeriod}'"))
			->andWhere(['NOT IN', 'sub.id', $paidInvoicesQuery])
			->andWhere([
				'sub.deleted' => Subscription::NO,
			]);

		return $subscriptionsQuery->all();
	}

	/**
	 * Creates a PDF file for an Invoice model and sends it to email.
	 *
	 * @param Subscription $subscription
	 * @param Invoice $invoice
	 * @return bool
	 */
	protected static function sendInvoiceToEmail($subscription, $invoice)
	{
		try {
			$invoiceEmailTemplateVariant = $invoice->status == Invoice::STATUS_PAID ? Template::EMAIL_VARIANT_INVOICE_PAYMENT_CONFIRMATION : Template::EMAIL_VARIANT_INVOICE_ISSUANCE;
			$invoiceEmailTemplate = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, $invoiceEmailTemplateVariant);
			if (!$invoiceEmailTemplate || !($invoiceEmailTemplateTranslation = $invoiceEmailTemplate->getTranslation())) {
				throw new \Exception('Missing invoice email template.');
			}
			$shortCodeValues = $invoice->getShortCodeValues();

			$email = Yii::$app->mailer->compose()
				->setTo([$subscription->subscriber->user->email => $subscription->subscriber->user->fullName])
				->setSubject(strtr($invoiceEmailTemplateTranslation->subject, $shortCodeValues))
				->setHtmlBody(strtr($invoiceEmailTemplateTranslation->content, $shortCodeValues));

			if (Yii::$app->settings->get('attachInvoiceToEmail', 'payment')) {
				$invoiceTemplate = Template::findDefaultByType(Template::TYPE_INVOICE);
				if (!$invoiceTemplate || !($invoiceTemplateTranslation = $invoiceTemplate->getTranslation())) {
					throw new \Exception('Missing invoice template.');
				}
				$fileName = Yii::$app->name . " #{$invoice->getDocumentSeriesNumber()}.pdf";
				$filePath = Yii::getAlias("@runtime/" . Yii::$app->security->generateRandomString(8) . "-{$fileName}");
				$pdf = new Pdf([
					'mode' => Pdf::MODE_UTF8,
					'format' => Pdf::FORMAT_A4,
					'orientation' => Pdf::ORIENT_PORTRAIT,
					'destination' => Pdf::DEST_FILE,
					'content' => strtr($invoiceTemplateTranslation->content, $invoice->getShortCodeValues(true)),
					'filename' => $filePath,
					'marginTop' => 5,
					'marginBottom' => 5,
				]);
				$pdfApi = $pdf->getApi();
				$pdfApi->setAutoTopMargin = true;
				$pdfApi->setAutoBottomMargin = true;
				$pdfApi->defaultheaderline = 0;
				$pdfApi->defaultfooterline = 0;
				if ($invoice->status == Invoice::STATUS_UNPAID) {
					$pdfApi->SetWatermarkText(mb_strtoupper(Invoice::getStatusLabels()[$invoice->status]['label']), 0.1);
					$pdfApi->showWatermarkText = true;
				}
				$pdf->render();
				$email->attach($pdf->filename, ['fileName' => $fileName]);
			}

			$email->send();

			if (isset($pdf)) {
				@unlink($pdf->filename);
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Saves the Invoice model.
	 *
	 * @param Subscription $subscription
	 * @return array|\yii\db\ActiveRecord|Invoice|null
	 */
	protected static function saveInvoice($subscription)
	{
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$currentDate = self::$currentDate->format('Y-m-d H:i:s');
			$invoiceIssuanceBeforePeriod = Yii::$app->settings->get('invoiceIssuanceBeforePeriod', 'payment');

			$maxPaidInvoicesQuery = Invoice::find()
				->select([
					'MAX([[id]]) AS id',
					'subscriber_id',
				])
				->andWhere([
					'status' => Invoice::STATUS_PAID,
					'deleted' => Invoice::NO,
				])
				->groupBy('subscriber_id');

			$maxUnpaidInvoicesQuery = Invoice::find()
				->select([
					'MAX([[id]]) AS id',
					'subscriber_id',
				])
				->andWhere([
					'status' => Invoice::STATUS_UNPAID,
					'deleted' => Invoice::NO,
				])
				->groupBy('subscriber_id');

			$invoice = Invoice::find()
				->alias('i')
				->joinWith([
					'items itm' => function (ActiveQuery $query) use ($subscription) {
						$query->andWhere([
							'itm.item_id' => $subscription->id,
							'itm.deleted' => Item::NO,
						]);
					},
				], false)
				->leftJoin(['ui' => $maxUnpaidInvoicesQuery], '[[ui.subscriber_id]] = [[i.subscriber_id]]')
				->leftJoin(['pi' => $maxPaidInvoicesQuery], '[[pi.subscriber_id]] = [[i.subscriber_id]]')
				->andWhere([
					'OR',
					new Expression("TIMESTAMPDIFF(DAY, [[i.issued_at]], '{$currentDate}') <= {$invoiceIssuanceBeforePeriod}"),
					new Expression("([[ui.id]] IS NOT NULL) AND ([[pi.id]] IS NOT NULL) AND ([[ui.id]] >= [[pi.id]])"),
					new Expression("([[ui.id]] IS NOT NULL) AND ([[pi.id]] IS NULL)"),
				])
				->andWhere([
					'i.status' => Invoice::STATUS_UNPAID,
					'i.deleted' => Invoice::NO,
				])
				->orderBy(['i.id' => SORT_DESC])
				->one();

			if ($invoice) {
				$dbTransaction->commit();
				return $invoice;
			}

			$invoice = new Invoice();
			$invoice->subscriber_id = $subscription->subscriber_id;
			$invoice->company_id = $subscription->company_id;
			$invoice->issued_at = self::$currentDate->format('Y-m-d H:i:s');
			$invoice->due_at = $subscription->end_at;
			$invoice->payment_method = $subscription->paymentMetadata->payment_method;
			$invoice->payment_processor = $subscription->paymentMetadata->payment_processor;
			$invoice->amount = $subscription->price;
			$invoice->currency = $subscription->currency;
			$invoice->vat = Yii::$app->settings->get('vatRate', 'general') ?: 0;
			$invoice->setSerializedValue('details', [
				'company' => $invoice->getCompanyDetails(),
				'client' => $invoice->getClientDetails(),
			]);
			$invoice->status = Invoice::STATUS_UNPAID;
			if (!$invoice->save()) {
				throw new \Exception('Invoice cannot be saved.');
			}

			$invoiceHasTemplate = new InvoiceHasTemplate();
			$invoiceHasTemplate->invoice_id = $invoice->id;
			$invoiceHasTemplate->template_id = Template::findDefaultByType(Template::TYPE_INVOICE)->id;
			if (!$invoiceHasTemplate->save()) {
				throw new \Exception('Invoice template cannot be saved.');
			}

			$item = new Item();
			$item->invoice_id = $invoice->id;
			$item->item_id = $subscription->id;
			$item->type = Item::TYPE_SUBSCRIPTION;
			$item->quantity = 1;
			$item->price = $subscription->price;
			$item->currency = $subscription->currency;
			$item->setSerializedValue('details', [
				'start_at' => $subscription->end_at,
				'end_at' => $subscription->getEndAtDate($subscription->end_at)->format('Y-m-d H:i:s'),
			]);
			$item->status = Item::STATUS_ACTIVE;
			if (!$item->save()) {
				throw new \Exception('Invoice item cannot be saved.');
			}

			if (Yii::$app->settings->get('enableInvoiceEmailNotification', 'payment')) {
				self::sendInvoiceToEmail($subscription, $invoice);
			}

			$dbTransaction->commit();
			return $invoice;
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return null;
		}
	}

	/**
	 * Checks the subscription for expiration and updates the models states.
	 *
	 * @param Subscription $subscription
	 * @param Invoice $invoice
	 * @return array|bool
	 */
	protected static function checkSubscription($subscription, $invoice)
	{
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$maxAllowedPaymentOverdueDate = new \DateTime($subscription->end_at);
			if ($allowedPaymentOverduePeriod = Yii::$app->settings->get('allowedPaymentOverduePeriod', 'payment')) {
				$maxAllowedPaymentOverdueDate->modify("+{$allowedPaymentOverduePeriod} days");
			}

			// Conditionally stop this process if the subscription is suspended
			if ($subscription->status == Subscription::STATUS_SUSPENDED) {
				// Nothing to do if the subscription overflows the allowed payment overdue period.
				// Otherwise, check the payment each 30 minutes.
				if (self::$currentDate >= $maxAllowedPaymentOverdueDate || self::$currentDate->format('i') != 30) {
					throw new \Exception('Subscription is expired.');
				}
			} elseif (self::$currentDate >= $maxAllowedPaymentOverdueDate) {
				// Suspend the subscription if overflows the allowed payment overdue period.
				$subscription->suspend();
				$dbTransaction->commit();
				// TODO: send email notification in this case or use a follow up marketing campaign email/sms?
				Yii::$app->trigger('subscription.afterSuspend', new \yii\base\Event(['sender' => $subscription]));
				return true;
			}

			// Check if subscription is configured for recurring payment.
			$paymentMetadata = $subscription->paymentMetadata;
			if (
				!$paymentMetadata
				|| $paymentMetadata->payment_method != PaymentMetadata::PAYMENT_METHOD_CARD
				|| empty($paymentMetadata->payment_processor)
				|| empty($paymentMetadata->payer_id)
				|| empty($paymentMetadata->payment_id)
				|| $paymentMetadata->recurring_payment != PaymentMetadata::YES
			) {
				throw new \Exception('This subscription is not configured for recurring payment.');
			}

			// Use a specific payment processor API
			$paymentProcessor = Yii::$app->payment->via($paymentMetadata->payment_processor);

			// Find the last recurring payment transactions for this subscription
			$transactions = $paymentProcessor->findTransactions([
				'payerId' => $paymentMetadata->payer_id,
				'paymentId' => $paymentMetadata->payment_id,
				'startDate' => (new \DateTime($subscription->end_at))->format('Y-m-d H:00:00'),
				'sort' => SORT_DESC,
			]);

			// Check the first transaction in the results since they are descendent sorted
			$transaction = reset($transactions);
			if (!$transaction || $transaction->status != $paymentProcessor::TRANSACTION_STATUS_SUCCEEDED) {
				throw new \Exception('The recurring payment was unsuccessful.');
			}

			// (Re)Activate subscription
			if (!$subscription->activate($subscription->end_at)) {
				throw new \Exception('The subscription cannot be activated.');
			}

			// Mark the Invoice as paid
			$invoice->setSerializedValue('details', [
				'transaction_id' => $transaction->id,
			]);
			$invoice->paid_at = self::$currentDate->format('Y-m-d H:i:s');
			$invoice->status = Invoice::STATUS_PAID;
			if (!$invoice->save()) {
				throw new \Exception('The invoice cannot be marked as paid.');
			}

			if (Yii::$app->settings->get('enableInvoiceEmailNotification', 'payment')) {
				self::sendInvoiceToEmail($subscription, $invoice);
			}

			Yii::$app->trigger('subscription.afterPayment', new \yii\base\Event(['sender' => $subscription]));

			$dbTransaction->commit();
			return true;
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
