<?php

namespace common\models;

use tws\behaviors\DefaultBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%assistant}}".
 *
 * @property int $id
 * @property int $vector_store_id
 * @property string $openai_id
 * @property string $name
 * @property string $gpt_model
 * @property string $instructions
 * @property string $temperature
 * @property string $top_p
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property VectorStore $vectorStore
 * @property Message[] $messages
 * @property User $creator
 * @property User $updater
 */
class Assistant extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%assistant}}';
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
			'DefaultBehavior' => [
				'class' => DefaultBehavior::class,
				'ensureDefaultValue' => true,
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
            [['vector_store_id', 'default', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['name', 'status'], 'required'],
            [['instructions'], 'string'],
            [['temperature', 'top_p'], 'number'],
            [['created_at', 'updated_at'], 'safe'],
            [['openai_id', 'name', 'gpt_model'], 'string', 'max' => 255],
            [['vector_store_id'], 'exist', 'skipOnError' => true, 'targetClass' => VectorStore::class, 'targetAttribute' => ['vector_store_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'vector_store_id' => Yii::t('label', 'Vector Store ID'),
            'openai_id' => Yii::t('label', 'OpenAI ID'),
            'name' => Yii::t('label', 'Name'),
            'gpt_model' => Yii::t('label', 'GPT Model'),
            'instructions' => Yii::t('label', 'Instructions'),
            'temperature' => Yii::t('label', 'Temperature'),
            'top_p' => Yii::t('label', 'Top P'),
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
     * @return \yii\db\ActiveQuery
     */
    public function getVectorStore()
    {
        return $this->hasOne(VectorStore::class, ['id' => 'vector_store_id']);
    }

	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getMessages()
	{
		return $this->hasMany(Message::class, ['assistant_id' => 'id']);
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
	public static function getGPTModels()
	{
		return [
			'gpt-4o' => 'gpt-4o - ' . Yii::t('backend', 'Fastest and most advanced model, supports text and image input'),
			'gpt-4o-mini' => 'gpt-4o-mini – ' . Yii::t('backend', 'Lightweight and efficient, optimized for quick tasks'),
			'gpt-4-turbo' => 'gpt-4-turbo – ' . Yii::t('backend', 'Optimized for speed and cost, high-performance GPT-4 variant'),
			'gpt-4' => 'gpt-4 – ' . Yii::t('backend', 'High reasoning capabilities, supports complex tasks'),
			'gpt-3.5-turbo' => 'gpt-3.5-turbo - ' . Yii::t('backend', 'Cost-effective and efficient, great for chat applications'),
		];
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

			$endpoint = 'https://api.openai.com/v1/assistants';

			$vectorStore = VectorStore::findOne($this->vector_store_id);

			$data = [
				'name' => $this->name,
				'instructions' => $this->instructions,
				'model' => $this->gpt_model,
				'top_p' => (float)$this->top_p ?? 1.0,
				'temperature' => (float)$this->temperature ?? 1.0,
				'tools' => [
					[
						'type' => 'code_interpreter',  // Tool for running Python code and data analysis
					],
					[
						'type' => 'file_search',       // Tool for searching within files
					],
				],
				'tool_resources' => [
					'file_search' => [
						'vector_store_ids' => [
							$vectorStore->openai_id
						]
					],
				],
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

			$endpoint = 'https://api.openai.com/v1/assistants/' . $openaiId;

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

			$endpoint = 'https://api.openai.com/v1/assistants/' . $openaiId;

			$vectorStore = VectorStore::findOne($this->vector_store_id);

			$data = [
				'name' => $this->name,
				'instructions' => $this->instructions,
				'model' => $this->gpt_model,
				'top_p' => (float)$this->top_p ?? 1.0,
				'temperature' => (float)$this->temperature ?? 1.0,
				'tools' => [
					[
						'type' => 'code_interpreter',  // Tool for running Python code and data analysis
					],
					[
						'type' => 'file_search',       // Tool for searching within files
					],
				],
				'tool_resources' => [
					'file_search' => [
						'vector_store_ids' => [
							$vectorStore->openai_id
						]
					],
				],
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

			$endpoint = 'https://api.openai.com/v1/assistants/' . $openaiId;

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
