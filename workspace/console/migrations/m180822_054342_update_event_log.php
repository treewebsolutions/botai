<?php
ini_set('memory_limit', '2048M');

use yii\db\Migration;

/**
 * Class m180822_054342_update_event_log
 */
class m180822_054342_update_event_log extends Migration
{
	/**
	 * @var array The old => new models map.
	 */
	public $modelsMap = [];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->modelsMap = [
			'common\models\ServiceDevice' => [
				'model' => 'common\models\Device',
				'module' => 'nomenclature-manager',
				'controller' => 'device',
				'resource' => 'Device',
			],
			'common\models\ServiceBrand' => [
				'model' => 'common\models\Manufacturer',
				'module' => 'nomenclature-manager',
				'controller' => 'manufacturer',
				'resource' => 'Manufacturer',
			],
			'common\models\ServiceDeviceModel' => [
				'model' => 'common\models\DeviceModel',
				'module' => 'nomenclature-manager',
				'controller' => 'device-model',
				'resource' => 'Device Model',
			],
			'common\models\ServiceColor' => [
				'model' => 'common\models\Color',
				'module' => 'nomenclature-manager',
				'controller' => 'color',
				'resource' => 'Color',
			],
			'common\models\ServiceClient' => [
				'model' => 'common\models\Client',
				'module' => 'client-manager',
				'controller' => 'client',
				'resource' => 'Client',
			],
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeUp()
	{
		$eventLogs = \common\models\EventLog::find();

		/** @var \common\models\EventLog $eventLog */
		foreach ($eventLogs->each(50) as $eventLog) {
			if (array_key_exists($eventLog->model, $this->modelsMap)) {
				$modelMap = $this->modelsMap[$eventLog->model];

				$eventLog->model = $modelMap['model'];
				$eventLog->module = $modelMap['module'];
				$eventLog->controller = $modelMap['controller'];
				$eventLog->resource = $modelMap['resource'];

				$eventLog->save(false);
			}

			$this->processData($eventLog, Yii::getAlias("@backend/runtime/eventlogs/{$eventLog->id}/{$eventLog->initial_data}"));
			$this->processData($eventLog, Yii::getAlias("@backend/runtime/eventlogs/{$eventLog->id}/{$eventLog->final_data}"));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function safeDown()
	{
		echo "m180822_054342_update_event_log cannot be reverted.\n";

		return false;
	}

	/**
	 * Processes initial and final data.
	 *
	 * @param \common\models\EventLog $eventLog
	 * @param string $filePath
	 * @return int|bool
	 */
	protected function processData($eventLog, $filePath)
	{
		if (!is_file($filePath)) {
			return false;
		}

		$data = file_get_contents($filePath);
		$data = strtr($data, [
			// Device
			'O:62:"backend\modules\service\modules\nomenclature\models\DeviceForm"' => 'O:46:"backend\modules\nomenclature\models\DeviceForm"',
			'O:27:"common\models\ServiceDevice"' => 'O:20:"common\models\Device"',
			'O:38:"common\models\ServiceDeviceTranslation"' => 'O:31:"common\models\DeviceTranslation"',

//			// Manufacturer
			'O:61:"backend\modules\service\modules\nomenclature\models\BrandForm"' => 'O:52:"backend\modules\nomenclature\models\ManufacturerForm"',
			'O:26:"common\models\ServiceBrand"' => 'O:26:"common\models\Manufacturer"',
			'O:35:"common\models\ServiceBrandHasDevice"' => 'O:35:"common\models\ManufacturerHasDevice"',
//			's:15:"brandHasDevices"' => 's:22:"manufacturerHasDevices"',
//			's:8:"brand_id"' => 's:15:"manufacturer_id"', // TODO: this causes error. The "C:11:"ArrayObject":11089" is the issue, the number must be updated too
			's:5:"brand"' => 's:12:"manufacturer"',
			's:6:"brands"' => 's:13:"manufacturers"',

			// DeviceModel
			'O:67:"backend\modules\service\modules\nomenclature\models\DeviceModelForm"' => 'O:51:"backend\modules\nomenclature\models\DeviceModelForm"',
			'O:74:"backend\modules\service\modules\nomenclature\models\ServiceDeviceModelForm"' => 'O:51:"backend\modules\nomenclature\models\DeviceModelForm"',
			'O:32:"common\models\ServiceDeviceModel"' => 'O:25:"common\models\DeviceModel"',

			// Color
			'O:61:"backend\modules\service\modules\nomenclature\models\ColorForm"' => 'O:45:"backend\modules\nomenclature\models\ColorForm"',
			'O:26:"common\models\ServiceColor"' => 'O:19:"common\models\Color"',
			'O:37:"common\models\ServiceColorTranslation"' => 'O:30:"common\models\ColorTranslation"',

			// ServiceClient => Client
			'O:56:"backend\modules\service\modules\client\models\ClientForm"' => 'O:40:"backend\modules\client\models\ClientForm"',
			'O:27:"common\models\ServiceClient"' => 'O:20:"common\models\Client"',
		]);

//		if ($eventLog->model == 'common\models\Device') {
//			$data = strtr($data, [
//				's:16:"deviceHasDefects"' => 's:23:"serviceDeviceHasDefects"',
//				's:7:"defects"' => 's:14:"serviceDefects"',
//				's:15:"deviceHasCauses"' => 's:22:"serviceDeviceHasCauses"',
//				's:6:"causes"' => 's:13:"serviceCauses"',
//				's:18:"deviceHasSolutions"' => 's:25:"serviceDeviceHasSolutions"',
//				's:9:"solutions"' => 's:16:"serviceSolutions"',
//			]);
//		} elseif ($eventLog->model == 'common\models\Manufacturer') {
//			$data = strtr($data, [
//				's:6:"inputs"' => 's:13:"serviceInputs"',
//			]);
//		} elseif ($eventLog->model == 'common\models\DeviceModel') {
//			$data = strtr($data, [
//				's:6:"inputs"' => 's:13:"serviceInputs"',
//			]);
//		} elseif ($eventLog->model == 'common\models\Color') {
//			$data = strtr($data, [
//				's:6:"inputs"' => 's:13:"serviceInputs"',
//			]);
//		} elseif ($eventLog->model == 'common\models\Client') {
//			$data = strtr($data, [
//				's:6:"inputs"' => 's:13:"serviceInputs"',
//				's:12:"inputRatings"' => 's:19:"serviceInputRatings"',
//			]);
//		}

		return file_put_contents($filePath, $data);
	}
}
