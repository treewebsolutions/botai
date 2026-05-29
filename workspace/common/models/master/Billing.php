<?php

namespace common\models\master;

use common\models\CommonActiveQuery;
use common\models\CommonActiveRecord;
use common\models\Country;
use tws\behaviors\DefaultBehavior;
use Yii;
use yii\behaviors\BlameableBehavior;
use yii\behaviors\TimestampBehavior;
use yii\helpers\Html;
use yii2tech\ar\softdelete\SoftDeleteBehavior;

/**
 * This is the model class for table "{{%billing}}".
 *
 * @property int $id
 * @property int $user_id
 * @property string $first_name
 * @property string $middle_name
 * @property string $last_name
 * @property string $company
 * @property string $tin
 * @property string $registration_number
 * @property string $address
 * @property string $street_name
 * @property string $street_number
 * @property string $staircase
 * @property string $block
 * @property string $floor
 * @property string $apartment
 * @property string $locality
 * @property string $zip_code
 * @property string $county
 * @property string $country
 * @property string $phone
 * @property string $email
 * @property string $bank_account
 * @property string $bank
 * @property int $default
 * @property int $created_by
 * @property int $updated_by
 * @property string $created_at
 * @property string $updated_at
 * @property int $status
 * @property int $deleted
 *
 * @property User $user
 * @property User $creator
 * @property User $updater
 *
 * @property string $fullName
 * @property string $companyDetails
 * @property string $fullAddress
 * @property string $formattedDisplay
 */
class Billing extends CommonActiveRecord
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
		return '{{%billing}}';
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
				'groupAttributes' => ['user_id'],
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
			[['user_id', 'status'], 'required'],
			[['user_id', 'default', 'created_by', 'updated_by', 'status', 'deleted'], 'integer'],
			[['created_at', 'updated_at'], 'safe'],
			[['created_at', 'updated_at'], 'default'],
			[['first_name', 'middle_name', 'last_name', 'company', 'tin', 'registration_number', 'address', 'street_name', 'street_number', 'staircase', 'block', 'floor', 'apartment', 'locality', 'zip_code', 'county', 'country', 'phone', 'email', 'bank_account', 'bank'], 'string', 'max' => 255],
			[['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
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
			'first_name' => Yii::t('label', 'First Name'),
			'middle_name' => Yii::t('label', 'Middle Name'),
			'last_name' => Yii::t('label', 'Last Name'),
			'company' => Yii::t('backend', 'Company'),
			'tin' => Yii::t('label', 'Tin'),
			'registration_number' => Yii::t('label', 'Registration Number'),
			'address' => Yii::t('label', 'Address'),
			'street_name' => Yii::t('label', 'Street Name'),
			'street_number' => Yii::t('label', 'Street Number'),
			'staircase' => Yii::t('label', 'Staircase'),
			'block' => Yii::t('label', 'Block'),
			'floor' => Yii::t('label', 'Floor'),
			'apartment' => Yii::t('label', 'Apartment'),
			'locality' => Yii::t('label', 'Locality'),
			'zip_code' => Yii::t('label', 'Zip Code'),
			'county' => Yii::t('label', 'County'),
			'country' => Yii::t('label', 'Country'),
			'phone' => Yii::t('label', 'Phone'),
			'email' => Yii::t('label', 'Email'),
			'bank_account' => Yii::t('label', 'Bank Account'),
			'bank' => Yii::t('label', 'Bank'),
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
	public function getUser()
	{
		return $this->hasOne(User::class, ['id' => 'user_id']);
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
	 * Gets the fullName.
	 *
	 * @return string
	 */
	public function getFullName()
	{
		return implode(' ', array_filter([
			$this->first_name,
			$this->middle_name,
			$this->last_name,
		]));
	}

	/**
	 * Gets the companyDetails.
	 *
	 * @return string
	 */
	public function getCompanyDetails()
	{
		return implode(' ', array_filter([
			$this->company,
			$this->tin,
			$this->registration_number,
		]));
	}

	/**
	 * Gets the fullAddress.
	 *
	 * @return string
	 */
	public function getFullAddress()
	{
		return implode(', ', array_filter([
			$this->street_name,
			$this->street_number,
			$this->locality,
			$this->zip_code,
			$this->county,
			$this->country ? Country::findAllCountries()[$this->country]->translation->name : null,
		]));
	}

	/**
	 * Gets the formattedDisplay.
	 *
	 * @return string
	 */
	public function getFormattedDisplay()
	{
		return static::formatForDisplay($this->attributes);
	}

	/**
	 * Formats a given data for display.
	 *
	 * @param array $data
	 * @return string|null
	 */
	public static function formatForDisplay($data)
	{
		if (empty($data)) {
			return null;
		}

		$name = implode(' ', array_filter([
			$data['first_name'],
			$data['middle_name'],
			$data['last_name'],
		]));
		$company = implode(', ', array_filter([
			$data['company'],
			$data['tin'] ? (Yii::t('label', 'Tin') . ": {$data['tin']}") : null,
			$data['registration_number'] ? (Yii::t('label', 'Tin') . ": {$data['registration_number']}") : null,
		]));
		$address = implode(', ', array_filter([
			$data['street_name'],
			$data['street_number'],
			$data['locality'],
			$data['zip_code'],
			$data['county'],
			$data['country'] ? Country::findAllCountries()[$data['country']]->translation->name : null,
		]));

		$content = [];
		$content[] = Html::beginTag('dl', ['class' => 'list-unstyled']);
		$content[] = Html::tag('dt', Yii::t('label', 'Contact Person'));
		$content[] = Html::tag('dd', "{$name} &mdash; {$data['phone']}");
		if (!empty($company)) {
			$content[] = Html::tag('dt', Yii::t('backend', 'Company'));
			$content[] = Html::tag('dd', $company);
		}
		$content[] = Html::tag('dt', Yii::t('label', 'Address'));
		$content[] = Html::tag('dd', $address);
		$content[] = Html::endTag('dl');

		return implode("\n", $content);
	}

	/**
	 * Finds all active records by User model ID(s).
	 *
	 * @param int|int[] $user_id
	 * @return array|static[]
	 */
	public static function findAllByUser($user_id)
	{
		return static::find()
			->andWhere([
				'user_id' => $user_id,
				'status' => static::STATUS_ACTIVE,
				'deleted' => static::NO,
			])
			->indexBy('id')
			->all();
	}

	/**
	 * Finds the default active record by User model ID.
	 *
	 * @param int $user_id
	 * @param bool $fallbackToLast Flag that indicates if the last record should be returned instead.
	 * @return array|static
	 */
	public static function findDefaultByUser($user_id, $fallbackToLast = false)
	{
		$query = static::find()->andWhere([
			'user_id' => $user_id,
			'status' => static::STATUS_ACTIVE,
			'deleted' => static::NO,
		]);

		$model = $query->andWhere(['default' => static::YES])->one();

		if ($fallbackToLast === true && !$model) {
			$model = $query->orderBy(['id' => SORT_DESC])->one();
		}

		return $model;
	}
}
