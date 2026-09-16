<?php

namespace backend\modules\setting\modules\integration\models;

use common\models\Integration;
use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * The hub's integration record, edited from the settings screens.
 *
 * The key itself lives in the `data` column as JSON, the same shape the tenants read,
 * so [[Integration::getApiKey()]] resolves it identically on both sides. It is exposed
 * here as its own attribute rather than as raw JSON, and - like the other secret
 * settings - the form renders it empty and a blank submission leaves the stored value
 * alone. Clearing a key is done by removing the integration.
 */
class IntegrationForm extends Integration
{
	/**
	 * @var string The API key, held apart from the JSON it is stored in.
	 */
	public $apiKey;

	/**
	 * @var string The key already stored, captured in [[afterFind()]].
	 */
	private $_storedApiKey;

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		if ($this->getIsNewRecord()) {
			$this->type = static::TYPE_OPENAI;
			$this->status = static::STATUS_ACTIVE;
		}
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['type'], 'required'],
			[['type'], 'in', 'range' => array_keys(static::getTypeLabels())],
			[['apiKey'], 'string', 'max' => 255],
			[['apiKey'], 'trim'],
			// Required on a new record only: on an update an empty field means
			// "leave the stored key alone".
			[['apiKey'], 'required', 'when' => function ($model) {
				return $model->getIsNewRecord();
			}, 'whenClient' => 'function () { return false; }'],
		]);
	}

	/**
	 * @inheritdoc
	 *
	 * An explicit list rather than Model::scenarios(): that would make every column
	 * writable straight from the request body, `default` and `status` included.
	 */
	public function scenarios()
	{
		return [
			static::SCENARIO_DEFAULT => ['name', 'type', 'apiKey', 'expire_at', 'sandbox', 'default', 'status'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
			'apiKey' => Yii::t('label', 'API Key'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();

		// Kept so an untouched field restores rather than blanks the stored key; never
		// pushed into $apiKey, so the value cannot reach the rendered form.
		$this->_storedApiKey = $this->getApiKey();
	}

	/**
	 * @inheritdoc
	 */
	public function beforeValidate()
	{
		if ((string) $this->apiKey === '' && (string) $this->_storedApiKey !== '') {
			$this->apiKey = $this->_storedApiKey;
		}

		return parent::beforeValidate();
	}

	/**
	 * @inheritdoc
	 */
	public function beforeSave($insert)
	{
		if (!parent::beforeSave($insert)) {
			return false;
		}

		// Merge rather than overwrite, so any other setting stored alongside survives.
		$this->data = Json::encode(ArrayHelper::merge($this->getDecodedData(), [
			'api_key' => (string) $this->apiKey,
		]));

		return true;
	}
}
