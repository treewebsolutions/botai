<?php

namespace frontend\models;

use common\models\Testimonial;
use common\models\TestimonialTranslation;
use Yii;
use yii\base\Model;

class TestimonialForm extends Model
{
	/**
	 * @var string The name.
	 */
	public $name;

	/**
	 * @var string The name.
	 */
	public $phone;

	/**
	 * @var string The organization.
	 */
	public $organization;

	/**
	 * @var float The rating.
	 */
	public $rating;

	/**
	 * @var string The message.
	 */
	public $message;

	/**
	 * @var string The honeypot field.
	 */
	public $workEmail;

    /**
     * @var string The honeypot field.
     */
    public $captchaResponse;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->rating = 5;
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name', 'phone', 'message', 'rating'], 'required'],
			['rating', 'number'],
			['organization', 'string'],
			['workEmail', 'safe'],
            ['captchaResponse', 'safe'],
        ];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'name' => Yii::t('label', 'Name'),
			'phone' => Yii::t('label', 'Phone'),
			'message' => Yii::t('label', 'Message'),
			'organization' => Yii::t('label', 'Organization'),
		];
	}

	/**
	 * Sends an email to the specified email address using the information collected by this model.
	 *
	 * @return bool whether the email was sent.
	 */
	public function save()
	{
		if (!empty($this->workEmail)) {
			return false;
		}
        if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')) {
            if (!empty($this->captchaResponse)) {
                $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . Yii::$app->settings->get('reCaptchaSecretKey', 'general') .'&response=' . $this->captchaResponse);
                $response = json_decode($result);
                if (empty($response->success)) {
                    return false;
                }
            }
        }

		$dbTransaction = Yii::$app->db->beginTransaction();
		try {
			$model = new Testimonial();
			$model->name = strip_tags(trim($this->name));
			$model->phone = strip_tags(trim($this->phone));
			$model->organization = strip_tags(trim($this->organization));
			$model->rating = $this->rating;
			$model->ip_address = Yii::$app->request->userIP;
			$model->status = Testimonial::STATUS_INACTIVE;
			if (!$model->save()) {
				$this->addErrors($model->getErrors());
				throw new \Exception();
			}

			$modelTranslation = new TestimonialTranslation();
			$modelTranslation->testimonial_id = $model->id;
			$modelTranslation->language_id = Yii::$app->language;
			$modelTranslation->message = strip_tags(trim($this->message));
			if (!$modelTranslation->save()) {
				$this->addErrors($modelTranslation->getErrors());
				throw new \Exception();
			}

			$dbTransaction->commit();
			return true;
		} catch (\Exception $e) {
			$dbTransaction->rollBack();
			return false;
		}
	}
}
