<?php

namespace frontend\modules\account\models;

use common\models\Invoice;
use common\models\InvoiceHasTemplate;
use common\models\Item;
use common\models\Subscription;
use common\models\SubscriptionFeature;
use common\models\Template;
use common\models\WorkspaceHasSubscriptionFeature;
use kartik\mpdf\Pdf;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;

class PaymentResult extends Model
{
	/**
	 * @var string|int The Subscription model ID.
	 */
	public $subscription_id;

	/**
	 * @var string|int The payment processor payer ID.
	 */
	public $payer_id;

	/**
	 * @var string|int The payment processor card ID.
	 */
	public $card_id;

	/**
	 * @var string|int The payment processor payment ID.
	 */
	public $payment_id;

	/**
	 * @var string|int The payment processor transaction ID.
	 */
	public $transaction_id;

	/**
	 * @var Subscription The Subscription model.
	 */
	private $_subscription;

	/**
	 * @var Invoice The Invoice model.
	 */
	private $_invoice;

	/**
	 * @var \DateTime The processing date and time instance.
	 */
	private $_processingDate;


	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->_processingDate = new \DateTime();
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['subscription_id'], 'required'],
			[['subscription_id'], 'exist', 'targetClass' => Subscription::class, 'targetAttribute' => ['subscription_id' => 'id'], 'skipOnError' => true],
			[['payer_id', 'card_id', 'payment_id',  'transaction_id'], 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'subscription_id' => Yii::t('label', 'Subscription ID'),
			'payer_id' => Yii::t('label', 'Payer ID'),
			'card_id' => Yii::t('label', 'Card ID'),
			'payment_id' => Yii::t('label', 'Payment ID'),
			'transaction_id' => Yii::t('label', 'Transaction ID'),
		]);
	}

	/**
	 * Gets the Subscription model.
	 *
	 * @return Subscription|null
	 */
	public function getSubscription()
	{
		if (!$this->_subscription) {
			$this->_subscription = Subscription::findOne([
				'id' => $this->subscription_id,
				'deleted' => Subscription::NO,
			]);
		}
		return $this->_subscription;
	}

	/**
	 * Gets the Invoice model.
	 *
	 * @return Invoice|null
	 */
	public function getInvoice()
	{
		return $this->_invoice;
	}

	/**
	 * Saves the Subscription model.
	 *
	 * @return bool
	 */
	protected function saveSubscription()
	{
		try {
			$subscription = $this->getSubscription();

			// (Re)Activate the subscription
			if ($subscription->status == Subscription::STATUS_SUSPENDED) {
				$subscription->start_at = $this->_processingDate->format('Y-m-d H:i:s');
				$subscription->end_at = $subscription->getEndAtDate()->format('Y-m-d H:i:s');
				if (!$subscription->activate($subscription->start_at)) {
					throw new \Exception('Subscription cannot be reactivated.');
				}
			} else {
				$startAt = $this->_processingDate->format('Y-m-d H:i:s');
				if (!$subscription->getIsOnTrialPeriod()) {
					$startAt = $subscription->end_at ?: $this->_processingDate->format('Y-m-d H:i:s');
				}
				if (!$subscription->activate($startAt)) {
					throw new \Exception('Subscription cannot be activated.');
				}
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	protected function renewFeaturesQuota()
	{
		try {
			$subscription = $this->getSubscription();
			$currentDate = (new \DateTime())->format('Y-m-d H:i:s');
			$renewableSubscriptionFeatures = SubscriptionFeature::find()
				->alias('sf')
				->select(['sf.id'])
				->joinWith(['subscription sub'])
				->andWhere(['!=', 'sub.type', Subscription::TYPE_FREE])
				->andWhere([
					'sf.renewable' => SubscriptionFeature::YES,
					'sub.status' => Subscription::STATUS_ACTIVE,
					'sub.deleted' => Subscription::NO,
					'sub.id' => $subscription->id,
				])
				->indexBy('id')
				->all();
			if ($renewableSubscriptionFeatures) {
				WorkspaceHasSubscriptionFeature::updateAll([
					'quota' => 0,
					'renewed_at' => $currentDate,
				], [
					'subscription_feature_id' => array_keys($renewableSubscriptionFeatures),
				]);
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the PaymentMetadata model.
	 *
	 * @return bool
	 */
	protected function savePaymentMetadata()
	{
		try {
			$paymentMetadata = $this->getSubscription()->paymentMetadata;
			$paymentMetadata->payer_id = (string) $this->payer_id;
			$paymentMetadata->payment_id = (string) $this->payment_id;
			if (!$paymentMetadata->save()) {
				throw new \Exception($paymentMetadata->getErrorSummary(false)[0]);
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the Invoice model.
	 *
	 * @return bool
	 */
	protected function saveInvoice()
	{
		try {
			$subscription = $this->getSubscription();

			/** @var Invoice $invoice */
			$proform = Invoice::find()
				->alias('i')
				->joinWith([
					'items itm' => function (ActiveQuery $query) use ($subscription) {
						$query->andWhere([
							'itm.item_id' => $subscription->id,
							'itm.type' => Item::TYPE_SUBSCRIPTION,
						]);
					}
				], false)
				->andWhere([
					'i.status' => Invoice::STATUS_UNPAID,
				])
				->orderBy(['i.issued_at' => SORT_DESC])
				->limit(1)
				->one();

			$invoice = new Invoice();
			$invoice->parent_id = $proform->id ?: null;
			$invoice->subscriber_id = $subscription->subscriber_id;
			$invoice->issued_at = $this->_processingDate->format('Y-m-d H:i:s');
			$invoice->company_id = $subscription->company_id;
			$invoice->paid_at = $this->_processingDate->format('Y-m-d H:i:s');
			$invoice->payment_method = $subscription->paymentMetadata->payment_method;
			$invoice->payment_processor = $subscription->paymentMetadata->payment_processor;
			$invoice->amount = $subscription->price;
			$invoice->currency = $subscription->currency;
            $invoice->vat = Yii::$app->settings->get('vatRate', 'general') ?: 0;
			$invoice->setSerializedValue('details', [
				'transaction_id' => $subscription->paymentMetadata->payment_id,
				'company' => $invoice->getCompanyDetails(),
				'client' => $invoice->getClientDetails(),
			]);
			$invoice->type = Invoice::TYPE_INVOICE;
			$invoice->status = Invoice::STATUS_PAID;
			if (!$invoice->save()) {
				throw new \Exception('Invoice cannot be saved.');
			}
			$this->_invoice = $invoice;


			$invoiceTemplate = Template::findDefaultByType(Template::TYPE_INVOICE);
			$invoiceHasTemplate = InvoiceHasTemplate::findOne([
				'invoice_id' => $invoice->id,
				'template_id' => $invoiceTemplate->id,
			]);
			if (!$invoiceHasTemplate) {
				$invoiceHasTemplate = new InvoiceHasTemplate();
				$invoiceHasTemplate->invoice_id = $invoice->id;
				$invoiceHasTemplate->template_id = Template::findDefaultByType(Template::TYPE_INVOICE)->id;
				if (!$invoiceHasTemplate->save()) {
					throw new \Exception('Invoice template cannot be saved.');
				}
			}

			$item = $invoice->getItems()
				->andWhere([
					'item_id' => $subscription->id,
					'type' => Item::TYPE_SUBSCRIPTION,
				])
				->limit(1)
				->one();

			if (!$item) {
				$item = new Item();
				$item->invoice_id = $invoice->id;
				$item->item_id = $subscription->id;
				$item->type = Item::TYPE_SUBSCRIPTION;
				$item->quantity = 1;
			}
			$item->price = $subscription->price;
			$item->currency = $subscription->currency;
			$item->setSerializedValue('details', [
				'start_at' => $subscription->start_at,
				'end_at' => $subscription->end_at,
			]);
			$item->status = Item::STATUS_ACTIVE;
			if (!$item->save()) {
				throw new \Exception('Invoice item cannot be saved.');
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Creates a PDF file for the Invoice model and sends it to email.
	 *
	 * @return bool
	 */
	protected function sendInvoiceToEmail()
	{
		try {
			$subscription = $this->getSubscription();
			$subscriber = $subscription->subscriber;
			$user = $subscriber->user;
			$invoice = $this->getInvoice();

			$invoiceEmailTemplate = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, Template::EMAIL_VARIANT_INVOICE_PAYMENT_CONFIRMATION);
			if (!$invoiceEmailTemplate || !($invoiceEmailTemplateTranslation = $invoiceEmailTemplate->getTranslation())) {
				throw new \Exception('Missing invoice email template.');
			}
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
			$shortCodeValues = $invoice->getShortCodeValues();

			Yii::$app->mailer->compose()
				->setTo([$user->email => $user->fullName])
				->setSubject(strtr($invoiceEmailTemplateTranslation->subject, $shortCodeValues))
				->setHtmlBody(strtr($invoiceEmailTemplateTranslation->content, $shortCodeValues))
				->attach($pdf->filename, ['fileName' => $fileName])
				->send();

			@unlink($pdf->filename);

			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the models.
	 *
	 * @return bool
	 */
	public function save()
	{
		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			if (!$this->validate()) {
				throw new \Exception();
			}
			if (!($subscription = $this->getSubscription())) {
				$this->addError('', Yii::t('frontend', 'Payment response is not valid.'));
				throw new \Exception();
			}
			// Do nothing if the subscription is already active or is cancelled
			if ($subscription->getIsOnTrialPeriod() || !in_array($subscription->status, [Subscription::STATUS_ACTIVE, Subscription::STATUS_CANCELLED])) {
				if (!$this->saveSubscription()) {
					throw new \Exception();
				}
				if (!$this->renewFeaturesQuota()) {
					throw new \Exception();
				}
				if (!$this->savePaymentMetadata()) {
					throw new \Exception();
				}
				if (!$this->saveInvoice()) {
					throw new \Exception();
				}
				$this->sendInvoiceToEmail();
			}
			$dbTransaction->commit();
			return true;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
