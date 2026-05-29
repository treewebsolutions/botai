<?php

use common\models\Feature;
use common\models\FeatureModule;
use common\models\User;
use yii\db\ActiveQuery;
use yii\db\Migration;

/**
 * Class m191211_113247_create_features
 */
class m191211_113247_create_features extends Migration
{
	/**
	 * {@inheritdoc}
	 * @throws \yii\db\Exception
	 */
	public function safeUp()
	{
		/** @var User $superAdminUser */
		$superAdminUser = User::find()
			->alias('u')
			->select(['u.id'])
			->joinWith([
				'authAssignment aa' => function (ActiveQuery $query) {
					$query->andWhere(new \yii\db\Expression('IF([[aa.item_name]] = "superAdmin", 1, 0) = 1'));
				},
			], false)
			->one();

		/** @var FeatureModule[] $featureModules */
		$featureModules = FeatureModule::find()->indexBy('name')->all();

		$columns = ['feature_module_id', 'name', 'created_by', 'updated_by', 'created_at', 'updated_at', 'status'];
		$rows = [];
		foreach (Feature::getFeatureLabels() as $featureName => $featureLabel) {
			$rows[] = [
				'feature_module_id' => $featureModules[FeatureModule::extractModuleNameFromFeatureName($featureName)]->id,
				'name' => $featureName,
				'created_by' => $superAdminUser->id,
				'updated_by' => $superAdminUser->id,
				'created_at' => (new DateTime)->format('Y-m-d H:i:s'),
				'updated_at' => (new DateTime)->format('Y-m-d H:i:s'),
				'status' => Feature::STATUS_ACTIVE,
			];
		}

		Yii::$app->db->createCommand()->batchInsert(Feature::tableName(), $columns, $rows)->execute();
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		Feature::deleteAll();
	}
}
