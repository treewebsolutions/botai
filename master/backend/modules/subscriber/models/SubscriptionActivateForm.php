<?php

namespace backend\modules\subscriber\models;

use common\models\Invoice;
use common\models\InvoiceHasTemplate;
use common\models\Item;
use common\models\PaymentMetadata;
use common\models\Subscription;
use common\models\SubscriptionFeature;
use common\models\Template;
use common\models\WorkspaceHasSubscriptionFeature;
use kartik\mpdf\Pdf;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class SubscriptionActivateForm extends Subscription
{
	/**
	 * @var Invoice The Invoice model.
	 */
	private $_invoice;

	public $issue_invoice;

	public $company;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
		    [['issue_invoice', 'company'], 'integer'],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'issue_invoice' => Yii::t('label', 'Issue Invoice'),
            'company' => Yii::t('common', 'Company'),
        ]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
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
	 * Saves the Invoice model.
	 *
	 * @return bool
	 */
	protected function saveInvoice()
	{
		try {
			$user = $this->subscriber->user;
			$invoice = new Invoice();
			$invoice->subscriber_id = $user->subscriber->id;
			if (empty($this->company)) {
                $invoice->company_id = $this->company_id;
            } else {
			    $invoice->company_id = $this->company;
            }
			$invoice->issued_at = (new \DateTime)->format('Y-m-d H:i:s');
			$invoice->payment_method = $this->paymentMetadata->payment_method ?: PaymentMetadata::PAYMENT_METHOD_BANK;
			$invoice->payment_processor = $this->paymentMetadata->payment_processor;
			$invoice->amount = $this->price;
			$invoice->currency = $this->currency;
            $invoice->vat = Yii::$app->settings->get('vatRate', 'general') ?: 0;
			$invoice->paid_at = (new \DateTime)->format('Y-m-d H:i:s');
			$invoice->setSerializedValue('details', [
				'company' => $invoice->getCompanyDetails(),
				'client' => $invoice->getClientDetails(),
                'payment' => $this->getPaymentDetails(),
			]);
			$invoice->status = Invoice::STATUS_PAID;
			if (!$invoice->save()) {
				throw new \Exception();
			}
			$this->_invoice = $invoice;

			$invoiceHasTemplate = new InvoiceHasTemplate();
			$invoiceHasTemplate->invoice_id = $invoice->id;
			$invoiceHasTemplate->template_id = Template::findDefaultByType(Template::TYPE_INVOICE)->id;
			if (!$invoiceHasTemplate->save()) {
				throw new \Exception();
			}

			$item = new Item();
			$item->invoice_id = $invoice->id;
			$item->item_id = $this->id;
			$item->type = Item::TYPE_SUBSCRIPTION;
			$item->quantity = 1;
			$item->price = $this->price;
			$item->currency = $this->currency;
			$item->setSerializedValue('details', [
			    'subscription' => '#' . $this->code . ' (' . $this->package->translation->name . ')',
				'start_at' => $this->start_at,
				'end_at' => $this->end_at,
        ]);
			$item->status = Item::STATUS_ACTIVE;
			if (!$item->save()) {
				throw new \Exception();
			}

			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	protected function renewFeaturesQuota()
	{
		try {
			$subscription = Subscription::find()
				->andWhere([
					'id' => $this->id,
				])
				->one();
			$currentDate = (new \DateTime())->format('Y-m-d H:i:s');
			$renewableSubscriptionFeatures = SubscriptionFeature::find()
				->alias('sf')
				->select(['sf.id'])
				->joinWith(['subscription sub'])
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
			$invoice = $this->getInvoice();
			$invoiceEmailTemplateVariant = $invoice->status == Invoice::STATUS_PAID ? Template::EMAIL_VARIANT_INVOICE_PAYMENT_CONFIRMATION : Template::EMAIL_VARIANT_INVOICE_ISSUANCE;
			$invoiceEmailTemplate = Template::findDefaultByTypeAndVariant(Template::TYPE_EMAIL, $invoiceEmailTemplateVariant);
			if (!$invoiceEmailTemplate || !($invoiceEmailTemplateTranslation = $invoiceEmailTemplate->getTranslation())) {
				throw new \Exception('Missing invoice email template.');
			}
			$shortCodeValues = $invoice->getShortCodeValues();

			$email = Yii::$app->mailer->compose()
				->setTo($this->subscriber->user->email)
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

			if (Yii::$app->settings->get('attachInvoiceToEmail', 'payment') && isset($pdf)) {
				@unlink($pdf->filename);
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * @inheritdoc
	 * @return bool|\yii\db\ActiveRecord|self
	 */
	public function activate($start_at = 'now', $trial = false, $end_at = null)
	{
	    $end_at = $this->end_at;
		$dbTransaction = static::getDb()->beginTransaction();
		try {
            if (!parent::activate($start_at, $trial, $end_at)) {
                throw new \Exception();
            }
			if (!$this->renewFeaturesQuota()) {
				throw new \Exception();
			}
			if ($this->issue_invoice) {
                if ($this->type != static::TYPE_FREE) {
                    if (!$this->saveInvoice()) {
                        throw new \Exception();
                    }
                    $this->sendInvoiceToEmail();
                }
            }
			$dbTransaction->commit();
			return $this;
		} catch(\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
