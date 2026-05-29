<?php

namespace common\models\master;

use common\helpers\Inflector;
use common\models\Country;
use tws\helpers\Url;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii2tech\ar\softdelete\SoftDeleteBehavior;
use common\models\CommonActiveQuery;
use common\models\CommonActiveRecord;

/**
 * This is the model class for table "{{%contractor}}".
 *
 * @property int $id
 * @property int $user_id
 * @property string $code
 * @property string $name
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $image
 * @property string $email
 * @property string $phone
 * @property string $fax
 * @property string $url
 * @property string $address
 * @property string $zip_code
 * @property string $locality
 * @property string $county
 * @property string $country
 * @property string $latitude
 * @property string $longitude
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property User $user
 * @property Workspace[] $workspaces
 * @property Breaktime[] $breaktimes
 * @property Location[] $locations
 * @property User $creator
 * @property User $updater
 */
class Contractor extends CommonActiveRecord
{
	/**
	 * @inheritdoc
	 * @throws \yii\base\InvalidConfigException
	 */
	public static function getDb()
	{
		return Yii::$app->get('masterDb');
	}

	/**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%contractor}}';
    }

	/**
	 * @inheritdoc
	 * @throws \Exception
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
			'SoftDeleteBehavior' => [
				'class' => SoftDeleteBehavior::class,
				'softDeleteAttributeValues' => [
					'deleted' => self::YES,
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
            [['code', 'name', 'status'], 'required'],
	        [['user_id', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
            [['created_at', 'updated_at'], 'safe'],
	        [['code'], 'string', 'max' => 8],
	        [['code'], 'unique'],
            [['name', 'first_name', 'middle_name', 'last_name', 'image', 'email', 'phone', 'fax', 'url', 'address', 'zip_code', 'locality', 'county', 'country', 'latitude', 'longitude'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
	        'user_id' => Yii::t('label', 'User ID'),
	        'code' => Yii::t('label', 'Code'),
            'name' => Yii::t('label', 'Name'),
	        'first_name' => Yii::t('label', 'First Name'),
	        'middle_name' => Yii::t('label', 'Middle Name'),
	        'last_name' => Yii::t('label', 'Last Name'),
            'image' => Yii::t('label', 'Image'),
            'email' => Yii::t('label', 'Email'),
            'phone' => Yii::t('label', 'Phone'),
            'fax' => Yii::t('label', 'Fax'),
            'url' => Yii::t('label', 'Url'),
            'address' => Yii::t('label', 'Address'),
            'zip_code' => Yii::t('label', 'Zip Code'),
            'locality' => Yii::t('label', 'Locality'),
            'county' => Yii::t('label', 'County'),
            'country' => Yii::t('label', 'Country'),
            'latitude' => Yii::t('label', 'Latitude'),
            'longitude' => Yii::t('label', 'Longitude'),
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
	public function getBreaktimes()
	{
		return $this->hasMany(Breaktime::class, ['contractor_id' => 'id']);
	}


	/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getLocations()
	{
		return $this->hasMany(Location::class, ['contractor_id' => 'id']);
	}


		/**
	 * @return \yii\db\ActiveQuery
	 */
	public function getUser()
	{
		return $this->hasOne(User::class, ['id' => 'user_id']);
	}

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getWorkspaces()
    {
        return $this->hasMany(Workspace::class, ['contractor_id' => 'id']);
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
	 * Gets the imageUrl with fallback to a blank image.
	 *
	 * @param bool $scheme
	 * @return string
	 */
	public function getImageUrl($scheme = false)
	{
		return Url::to("@uploads/contractor/{$this->id}/{$this->image}", $scheme);
	}

	/**
	 * Gets the fullAddress.
	 *
	 * @return string
	 */
	public function getFullAddress()
	{
		return implode(', ', array_filter([
			$this->address,
			$this->locality,
			$this->zip_code,
			$this->county,
			$this->country ? Country::findAllCountries()[$this->country]->name : null,
		]));
	}

	/**
	 * Finds all active records.
	 *
	 * @param null|int $status
	 * @return mixed
	 */
	public static function findAllContractors($status = null)
	{
		$query = static::find()
			->alias('c')
			->where([
				'c.deleted' => self::NO,
			])
			->orderBy([
				'c.name' => SORT_ASC,
			])
			->indexBy('id');

		if ($status) {
			$query->andWhere(['c.status' => $status]);
		}
		return $query->all();
	}

	/**
	 * Finds user by its unique attributes (it can be any of username, email or phone).
	 *
	 * @param array $attributes
	 * @return array|\yii\db\ActiveRecord|static|null
	 */
	public static function findByAttributes($attributes)
	{
		$model = static::find()
			->andWhere([
				'deleted' => static::NO,
			])
			->andWhere(['=', 'email', $attributes['email']])
			->one();

		if ($model) {
			return $model;
		}
	}

	/**
	 * Creates a new model in master application.
	 * This method also check for an existing model by its unique attributes.
	 *
	 * @param array $attributes
	 * @return bool|static
	 */
	public static function createModel($attributes)
	{
		try {
			$model = static::findByAttributes($attributes);
			if (!$model) {
				$model = new static();
				$model->setAttributes($attributes);
				$model->deleted = static::NO;
				if (!$model->save()) {
					throw new \Exception('Cannot save master model.');
				}
				$workspace = Workspace::find()
					->select([
						'*',
					])
					->where(['contractor_id' => $model->id])
					->one();
				if (!$workspace) {
					$curl = curl_init();
					curl_setopt_array($curl, [
						CURLOPT_URL => Yii::$app->request->hostInfo . '/api/v1/workspaces',
						CURLOPT_RETURNTRANSFER => true,
						CURLOPT_ENCODING => '',
						CURLOPT_MAXREDIRS => 10,
						CURLOPT_TIMEOUT => 0,
						CURLOPT_FOLLOWLOCATION => true,
						CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						CURLOPT_AUTOREFERER => true,
						CURLOPT_SSL_VERIFYHOST => 3,
						CURLOPT_SSL_VERIFYPEER => false,
						CURLOPT_CUSTOMREQUEST => 'POST',
						CURLOPT_POSTFIELDS => [
							'status' => static::STATUS_ACTIVE,
							'contractor_id' => $model->id,
							'url' => Inflector::slug($attributes['name']),
							'bypass' => '1'
						],
						CURLOPT_HTTPHEADER => [
							'Accept-Language: ' . Yii::$app->language,
							'Authorization: Bearer ' . Yii::$app->user->identity->getAuthKey(),
						],
					]);
					$response = curl_exec($curl);
					$error = curl_error($curl);
					curl_close($curl);
					if (!$error) {
						$result = json_decode($response, true);
						$data = $result['data'];
					}
					if (empty($data['id'])) {
						throw new \Exception('Cannot save workspace model.');
					}
				}
			}
			return $model;
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Updates an existing User model in master application.
	 *
	 * @param int $id
	 * @param array $attributes
	 * @return bool|self
	 */
	public static function updateModel($id, $attributes)
	{
		try {
			if (!($model = static::findOne($id))) {
				throw new \Exception();
			}
			$model->setAttributes($attributes);
			if (!$model->save()) {
				throw new \Exception('Cannot save master model.');
			}
			$workspace = Workspace::find()
				->select([
					'*',
				])
				->where(['contractor_id' => $model->id])
				->one();
			if (!$workspace) {
				$curl = curl_init();
				curl_setopt_array($curl, [
					CURLOPT_URL => Yii::$app->request->hostInfo . '/api/v1/workspaces',
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_ENCODING => '',
					CURLOPT_MAXREDIRS => 10,
					CURLOPT_TIMEOUT => 0,
					CURLOPT_FOLLOWLOCATION => true,
					CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					CURLOPT_AUTOREFERER => true,
					CURLOPT_SSL_VERIFYHOST => 3,
					CURLOPT_SSL_VERIFYPEER => false,
					CURLOPT_CUSTOMREQUEST => 'POST',
					CURLOPT_POSTFIELDS => [
						'status' => static::STATUS_ACTIVE,
						'contractor_id' => $model->id,
						'url' => Inflector::slug($attributes['name']),
						'bypass' => '1'
					],
					CURLOPT_HTTPHEADER => [
						'Accept-Language: ' . Yii::$app->language,
						'Authorization: Bearer ' . Yii::$app->user->identity->getAuthKey(),
					],
				]);
				$response = curl_exec($curl);
				$error = curl_error($curl);
				curl_close($curl);
				if (!$error) {
					$result = json_decode($response, true);
					$data = $result['data'];
				}
				if (empty($data['id'])) {
					throw new \Exception('Cannot save workspace model.');
				}
			}
			return $model;
		} catch (\Exception $e) {
			return false;
		}
	}

	public static function linkWorkspaces($contractor_id, $workspace_ids, $sync = false)
	{
		try {
			if (!empty($workspace_ids)) {
				foreach ($workspace_ids as $workspace_id) {
					$model = WorkspaceHasContractor::find()
						->where([
							'workspace_id' => $workspace_id,
							'contractor_id' => $contractor_id,
						])
						->all();
					if (empty($model)) {
						$model = new WorkspaceHasContractor();
						$model->workspace_id = $workspace_id;
						$model->contractor_id = $contractor_id;
						$model->save();
					}
				}
			}
			if ($sync) {
				$model = Contractor::findOne(['id' => $contractor_id]);
				$workspace_ids = ArrayHelper::getColumn(WorkspaceHasContractor::find()->where(['contractor_id' => $contractor_id])->andWhere(['<>', 'workspace_id', $workspace_ids[0]])->all(), 'workspace_id');
				if (!empty($workspace_ids)) {
					foreach ($workspace_ids as $workspace_id) {
						$workspace = Workspace::findOne(['id' => $workspace_id]);
						$workspaceDb = $workspace->getWorkspaceDb();
						$query = (new Query())
							->select([
								'*'
							])
							->from([
								'{{%contractor}}'
							])
							->where([
								'id' => $contractor_id
							]);
						$contractor = $query->createCommand($workspaceDb)->queryOne();
						$attributes = $model->attributes;
						unset($attributes['user_id']);
						unset($attributes['working_point_id']);
						unset($attributes['workspace_id']);
						unset($attributes['created_by']);
						unset($attributes['created_at']);
						unset($attributes['updated_by']);
						unset($attributes['updated_at']);
						if (!empty($contractor)) {
							$workspaceDb->createCommand()->update('{{%contractor}}',
								$attributes,
								[
									'id' => $contractor_id
								])->execute();
						}
					}
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}
}
