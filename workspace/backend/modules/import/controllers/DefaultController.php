<?php

namespace backend\modules\import\controllers;

use yii\filters\AccessControl;
use yii\web\Controller;

/**
 * Default controller for the `import` module
 */
class DefaultController extends Controller
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'roles' => ['@'],
					],
				],
			],
		];
	}

	/**
	 * Renders the index view for the module.
	 *
	 * @return string
	 */
	public function actionIndex()
	{
		return $this->redirect(['spreadsheet/index'], 301);
	}
}
