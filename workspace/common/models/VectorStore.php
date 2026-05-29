<?php

namespace common\models;

use tws\behaviors\DateTimeBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\caching\TagDependency;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%vector_store}}".
 *
 * @property int $id
 * @property string $openai_id
 * @property string $name
 * @property int $tokens_per_file
 * @property string $expire_at
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property Assistant[] $assistants
 * @property VectorStoreFile[] $vectorStoreFiles
 * @property User $creator
 * @property User $updater
 */
class VectorStore extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%vector_store}}';
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
            [['tokens_per_file', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['expire_at', 'created_at', 'updated_at'], 'safe'],
            [['openai_id', 'name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'openai_id' => Yii::t('label', 'OpenAI ID'),
            'name' => Yii::t('label', 'Name'),
            'tokens_per_file' => Yii::t('label', 'Tokens Per File'),
            'expire_at' => Yii::t('label', 'Expire At'),
            'created_by' => Yii::t('label', 'Created By'),
            'updated_by' => Yii::t('label', 'Updated By'),
            'created_at' => Yii::t('label', 'Created At'),
            'updated_at' => Yii::t('label', 'Updated At'),
            'status' => Yii::t('label', 'Status'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAssistants()
    {
        return $this->hasMany(Assistant::class, ['vector_store_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getVectorStoreFiles()
    {
        return $this->hasMany(VectorStoreFile::class, ['vector_store_id' => 'id']);
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
	 * Finds all active records.
	 *
	 * @return static[]|array
	 */
	public static function findAllVectorStores()
	{
		try {
			return static::getDb()->cache(function ($db) {
				return static::find()
					->alias('vs')
					->where([
						'vs.status' => static::STATUS_ACTIVE,
						'vs.deleted' => static::NO,
					])
					->orderBy(['vs.name' => SORT_ASC])
					->indexBy('id')
					->all();
			}, 0, new TagDependency(['tags' => __FUNCTION__]));
		} catch (\Throwable $e) {
			return [];
		}
	}


	//region OpenAI CRUD
	/**
	 * Create record in OpenAI
	 *
	 * @return string|null
	 */
	public function createInOpenAI()
	{
		try {
			$integration = Integration::find()
				->where([
					'status' => Integration::STATUS_ACTIVE,
					'deleted' => Integration::NO,
					'type' => Integration::TYPE_OPENAI,
					'default' => Integration::YES,
				])
				->one();
			$apiKey = $integration->data;
			if (!$apiKey) {
				throw new \Exception(Yii::t('backend', 'OpenAI API key is missing.'));
			}

			$endpoint = 'https://api.openai.com/v1/vector_stores';

			$expiresAfter = null;
			if ($this->expire_at) {
				$expiresAfter = [
					'anchor' => 'last_active_at',
					'days' => (int) floor((strtotime($this->expire_at) - time()) / (60 * 60 * 24))
				];
			}
			$data = [
				'name' => $this->name,
				'expires_after' => $expiresAfter,
			];

			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authorization: ' . 'Bearer ' . $apiKey,
				'Content-Type: application/json',
				'OpenAI-Beta: assistants=v2',
			]);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

			$response = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);

			if ($error) {
				throw new \Exception(Yii::t('backend', 'OpenAI API Error') . ': ' . $error);
			}

			$decodedResponse = json_decode($response, true);

			if (isset($decodedResponse['error'])) {
				throw new \Exception(Yii::t('backend', 'OpenAI API Response Error') . ': ' . json_encode($decodedResponse));
			}

			if (!isset($decodedResponse['id'])) {
				throw new \Exception(Yii::t('backend', 'Invalid OpenAI API response') . ': ' . json_encode($decodedResponse));
			}

			return $decodedResponse;
		} catch (\Exception $e) {
			$this->addError('', $this->getCustomErrorMessage($e));
			return false;
		}
	}

	/**
	 * Retrieves record from OpenAI
	 *
	 * @param string $openaiId
	 * @return mixed
	 */
	public function readFromOpenAI($openaiId)
	{
		try {
			$integration = Integration::find()
				->where([
					'status' => Integration::STATUS_ACTIVE,
					'deleted' => Integration::NO,
					'type' => Integration::TYPE_OPENAI,
					'default' => Integration::YES,
				])
				->one();
			$apiKey = $integration->data;
			if (!$apiKey) {
				throw new \Exception(Yii::t('backend', 'OpenAI API key is missing.'));
			}

			$endpoint = 'https://api.openai.com/v1/vector_stores/' . $openaiId;

			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authorization: ' . 'Bearer ' . $apiKey,
				'Content-Type: application/json',
				'OpenAI-Beta: assistants=v2',
			]);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');

			$response = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);

			if ($error) {
				throw new \Exception(Yii::t('backend', 'OpenAI API Error') . ': ' . $error);
			}

			$decodedResponse = json_decode($response, true);
			if (isset($decodedResponse['error'])) {
				throw new \Exception(Yii::t('backend', 'OpenAI API Response Error') . ': ' . json_encode($decodedResponse));
			}

			return $decodedResponse;
		} catch (\Exception $e) {
			$this->addError('', $this->getCustomErrorMessage($e));
			return false;
		}
	}

	/**
	 * Update record in OpenAI
	 *
	 * @param string $openaiId
	 * @return mixed
	 */
	public function updateInOpenAI($openaiId)
	{
		try {
			$integration = Integration::find()
				->where([
					'status' => Integration::STATUS_ACTIVE,
					'deleted' => Integration::NO,
					'type' => Integration::TYPE_OPENAI,
					'default' => Integration::YES,
				])
				->one();
			$apiKey = $integration->data;
			if (!$apiKey) {
				throw new \Exception(Yii::t('backend', 'OpenAI API key is missing.'));
			}

			$endpoint = 'https://api.openai.com/v1/vector_stores/' . $openaiId;

			$expiresAfter = null;
			if ($this->expire_at) {
				$expiresAfter = [
					'anchor' => 'last_active_at',
					'days' => (int) floor((strtotime($this->expire_at) - time()) / (60 * 60 * 24))
				];
			}
			$data = [
				'name' => $this->name,
				'expires_after' => $expiresAfter,
			];

			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authorization: ' . 'Bearer ' . $apiKey,
				'Content-Type: application/json',
				'OpenAI-Beta: assistants=v2',
			]);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

			$response = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);

			if ($error) {
				throw new \Exception(Yii::t('backend', 'OpenAI API Error') . ': ' . $error);
			}

			$decodedResponse = json_decode($response, true);

			if (isset($decodedResponse['error'])) {
				throw new \Exception(Yii::t('backend', 'OpenAI API Response Error') . ': ' . json_encode($decodedResponse));
			}

			if (!isset($decodedResponse['id'])) {
				throw new \Exception(Yii::t('backend', 'Invalid OpenAI API response') . ': ' . json_encode($decodedResponse));
			}

			return $decodedResponse;
		} catch (\Exception $e) {
			$this->addError('', $this->getCustomErrorMessage($e));
			return false;
		}
	}

	/**
	 * Deletes the record from OpenAI
	 *
	 * @param string $openaiId
	 * @return bool
	 */
	public function deleteFromOpenAI($openaiId)
	{
		try {
			$integration = Integration::find()
				->where([
					'status' => Integration::STATUS_ACTIVE,
					'deleted' => Integration::NO,
					'type' => Integration::TYPE_OPENAI,
					'default' => Integration::YES,
				])
				->one();
			$apiKey = $integration->data;
			if (!$apiKey) {
				throw new \Exception(Yii::t('backend', 'OpenAI API key is missing.'));
			}

			$endpoint = 'https://api.openai.com/v1/vector_stores/' . $openaiId;

			// cURL request to delete the vector store from OpenAI
			$ch = curl_init($endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, [
				'Authorization: ' . 'Bearer ' . $apiKey,
				'Content-Type: application/json',
				'OpenAI-Beta: assistants=v2',
			]);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

			$response = curl_exec($ch);
			$error = curl_error($ch);
			curl_close($ch);

			if ($error) {
				throw new \Exception(Yii::t('backend', 'OpenAI API Error') . ': ' . $error);
			}

			$decodedResponse = json_decode($response, true);
			if (isset($decodedResponse['error'])) {
				throw new \Exception(Yii::t('backend', 'OpenAI API Response Error') . ': ' . json_encode($decodedResponse));
			}

			return true;
		} catch (\Exception $e) {
			$this->addError('', $this->getCustomErrorMessage($e));
			return false;
		}
	}
	//endregion OpenAI
}
