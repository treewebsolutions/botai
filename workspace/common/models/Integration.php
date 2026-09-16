<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use tws\behaviors\DefaultBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%integration}}".
 *
 * @property int $id
 * @property string $name
 * @property string $data
 * @property string $expire_at
 * @property int $type
 * @property int $sandbox
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property User $creator
 * @property User $updater
 */
class Integration extends CommonActiveRecord
{

	/**
	 * Same value as the hub's Integration::TYPE_OPENAI, so an integration type means
	 * the same thing in every database. 1 is left free: it is SPV on the hub, which
	 * tenants do not have, and skipping it keeps the two numberings aligned.
	 */
	const TYPE_OPENAI = 2;

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%integration}}';
    }

	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'BlameableBehavior' => [
				'class' => BlameableBehavior::class,
			],
			'TimestampBehavior' => [
				'class' => TimestampBehavior::class,
				'value' => (new \DateTime)->format('Y-m-d H:i:s'),
			],
			'DateTimeBehavior' => [
				'class' => DateTimeBehavior::class,
				'attributes' => ['expire_at'],
			],
			'DefaultBehavior' => [
				'class' => DefaultBehavior::class,
				'groupAttributes' => ['type'],
			],
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => static::YES,
				],
			],
		];
	}

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name', 'status'], 'required'],
            [['data'], 'string'],
            [['expire_at', 'created_at', 'updated_at'], 'safe'],
            [['type', 'sandbox', 'default', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'name' => Yii::t('label', 'Name'),
            'data' => Yii::t('label', 'Data'),
            'expire_at' => Yii::t('label', 'Expire At'),
            'type' => Yii::t('label', 'Type'),
            'sandbox' => Yii::t('label', 'Sandbox'),
            'default' => Yii::t('label', 'Default'),
            'created_by' => Yii::t('label', 'Created By'),
            'updated_by' => Yii::t('label', 'Updated By'),
            'created_at' => Yii::t('label', 'Created At'),
            'updated_at' => Yii::t('label', 'Updated At'),
            'status' => Yii::t('label', 'Status'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getCreator()
	{
		return $this->hasOne(User::class, ['id' => 'created_by']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getUpdater()
	{
		return $this->hasOne(User::class, ['id' => 'updated_by']);
	}

	/**
	 * Model type labels.
	 *
	 * @return array
	 */
	public static function getTypeLabels()
	{
		return [
			static::TYPE_OPENAI => Yii::t('label', 'OpenAI'),
		];
	}

	/**
	 * Decodes the data JSON column.
	 *
	 * @return array
	 */
	public function getDecodedData(): array
	{
		$decoded = json_decode((string) $this->data, true);
		if (is_array($decoded)) {
			return $decoded;
		}

		// Backward compatibility: a plain string is treated as the api_key
		if (!empty($this->data)) {
			return ['api_key' => trim((string) $this->data)];
		}

		return [];
	}

	/**
	 * Returns the API key from the decoded data.
	 *
	 * @return string|null
	 */
	public function getApiKey(): ?string
	{
		$apiKey = $this->getDecodedData()['api_key'] ?? null;
		return $apiKey !== null && $apiKey !== '' ? (string) $apiKey : null;
	}

	/**
	 * Returns a specific setting from the decoded data.
	 *
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	public function getSetting(string $key, $default = null)
	{
		return $this->getDecodedData()[$key] ?? $default;
	}

	/**
	 * Finds the OpenAI integration that provides the API key: the default one first,
	 * then any active OpenAI integration (a key saved without the "Default" checkbox still works).
	 *
	 * @return static|null
	 */
	public static function findOpenAI()
	{
		return static::find()
			->where([
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
				'type' => static::TYPE_OPENAI,
			])
			->orderBy(['default' => SORT_DESC, 'id' => SORT_ASC])
			->one();
	}

	/**
	 * The OpenAI integration whose key this tenant actually runs on.
	 *
	 * The tenant's own comes first - default, then any active one. A tenant that has
	 * configured nothing falls back to the platform's integration in the master
	 * database, so the chat works out of the box and a tenant can take it over at any
	 * point simply by saving its own key.
	 *
	 * @return \common\models\Integration|\common\models\master\Integration|null
	 */
	public static function resolveOpenAI()
	{
		$own = static::findOpenAI();
		if ($own !== null && (string) $own->getApiKey() !== '') {
			return $own;
		}

		$platform = master\Integration::findPlatformOpenAI();

		return $platform !== null && (string) $platform->getApiKey() !== '' ? $platform : $own;
	}

	/**
	 * Whether the key in use belongs to the platform rather than to this tenant.
	 *
	 * Surfaced in the integrations screen so an administrator can tell which account the
	 * usage is billed against.
	 *
	 * @return bool
	 */
	public static function isUsingPlatformOpenAI()
	{
		$resolved = static::resolveOpenAI();

		return $resolved instanceof master\Integration;
	}

}
