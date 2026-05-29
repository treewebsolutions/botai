<?php

namespace api\v1\modules\contractor\controllers;

use common\models\Contractor;
use Yii;
use yii\filters\AccessControl;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\ArrayHelper;
use yii\rest\Controller;

class ContractorController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public $modelClass = 'api\v1\modules\contractor\models\Contractor';

	public function __construct($id, $module, $config = [])
	{
		parent::__construct($id, $module, $config);
	}

	/**
	 * {@inheritdoc}
	 */
	public function behaviors()
	{
		return ArrayHelper::merge(parent::behaviors(), [
			'authenticator' => [
				'class' => HttpBearerAuth::class,
			],
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'actions' => ['clocking'],
						'roles' => ['@'],
					],
				],
			],
		]);
	}

	/**
	 * Clocking Activity for [[Contractor]] models.
	 *
	 * @return mixed
	 */
	public function actionClocking()
	{
		$requestParams = Yii::$app->request->bodyParams;
		return Contractor::clockingActivity($requestParams);
	}
}
