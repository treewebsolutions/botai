<?php

namespace backend\modules\setting\models;

use common\models\Setting;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

class EmailSettingForm extends Setting
{
	/**
	 * @var string The email address from which the emails will be sent.
	 */
	public $from;

	/**
	 * @var string The email address to which the emails will be sent.
	 */
	public $replyTo;

	/**
	 * @var bool Flag that indicates if custom SMTP server should be used.
	 */
	public $useCustomSMTP;

	/**
	 * @var string The SMTP server host.
	 */
	public $host;

	/**
	 * @var int The SMTP server port.
	 */
	public $port = 25;

	/**
	 * @var string The SMTP server encryption.
	 */
	public $encryption = 'tls';

	/**
	 * @var string The SMTP server username.
	 */
	public $username;

	/**
	 * @var string The SMTP server password.
	 */
	public $password;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['from', 'replyTo', 'host', 'encryption', 'username', 'password'], 'string'],
			[['from', 'replyTo', 'host', 'port', 'encryption', 'username', 'password'], 'trim'],
			[['from', 'replyTo'], 'email'],
			[['port'], 'integer'],

			[['useCustomSMTP'], 'boolean'],
			[['host', 'port', 'encryption', 'username', 'password'], 'required', 'when' => function () {
				return $this->useCustomSMTP;
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[useCustomSMTP]\"]:checkbox").prop("checked");
			}'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'from' => Yii::t('label', 'From'),
			'replyTo' => Yii::t('label', 'Reply To'),
			'useCustomSMTP' => Yii::t('label', 'Use Custom SMTP'),
			'host' => Yii::t('label', 'Host'),
			'port' => Yii::t('label', 'Port'),
			'encryption' => Yii::t('label', 'Encryption'),
			'username' => Yii::t('label', 'Username'),
			'password' => Yii::t('label', 'Password'),
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
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		$this->setAttributes($this->getUnserializedValue('setting'));
	}

	/**
	 * Saves the setting.
	 *
	 * @return bool|\yii\db\ActiveRecord
	 */
	public function saveModel()
	{
		$this->name = 'email';
		$this->type = static::TYPE_APP;
		$this->status = static::STATUS_ACTIVE;
		$this->setSerializedValue('setting', [
			'from' => $this->from,
			'replyTo' => $this->replyTo,
			'useCustomSMTP' => $this->useCustomSMTP,
			'host' => $this->host,
			'port' => $this->port,
			'encryption' => $this->encryption,
			'username' => $this->username,
			'password' => $this->password,
		]);

		return $this->save() ? $this : null;
	}
}
