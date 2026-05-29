<?php

namespace frontend\modules\account\controllers;

use common\models\User;
use frontend\controllers\MainController;
use frontend\modules\account\models\ProfileForm;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class ProfileController extends MainController
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
						'actions' => ['index', 'upload-file'],
						'roles' => ['@'],
					],
				],
			],
		];
	}

	/**
	 * Displays index view.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionIndex()
	{
		$model = $this->findModel(Yii::$app->user->id, ProfileForm::class);

		if ($model->load(Yii::$app->request->post()) && $model->saveModel()) {
			$user = User::findOne(['id' => $model->id]);
			if (Yii::$app->getUser()->login($user, (int) Yii::$app->settings->get('userLoginDuration'))) {
				Yii::$app->session->setFlash('success', Yii::t('common', 'Your profile was successfully updated.'));
				return $this->refresh();
			}
		}

		return $this->render('index', [
			'model' => $model,
		]);
	}

	/**
	 * Uploads a new file.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionUploadFile()
	{
		$model = $this->findModel(Yii::$app->user->id);
		$bodyParams = Yii::$app->request->post();
		$attribute = $bodyParams['attribute'];
		$fileName = $bodyParams['fileName'];
		$response = [];

		try {
			if (!$model->hasAttribute($attribute) || !($file = UploadedFile::getInstanceByName($fileName))) {
				throw new \Exception();
			}

			$dirPath = Yii::getAlias("@uploads/user/{$model->id}");
			$oldFilePath = "{$dirPath}/{$model->oldAttributes['image']}";
			$fileName = StringHelper::truncate(implode('_', array_filter([
				Inflector::slug($model->fullName),
				Yii::$app->security->generateRandomString(8),
			])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";
			$filePath = "{$dirPath}/{$fileName}";

			FileHelper::createDirectory($dirPath);
			if (!$file->saveAs($filePath)) {
				throw new \Exception();
			}
			if (!$model->updateAttributes([$attribute => $fileName])) {
				throw new \Exception();
			}
			if (is_file($oldFilePath) && $oldFilePath != $filePath) {
				FileHelper::unlink($oldFilePath);
			}
			$response['success'] = true;
		} catch (\Exception $e) {
			$response['success'] = false;
			$response['error'] = Yii::t('common', 'Cannot upload the file.');
		}

		return $this->asJson($response);
	}

	/**
	 * Finds the User model(s) based on its primary key value.
	 * A 404 HTTP exception will be thrown if no record was found.
	 *
	 * @param int|array $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @param bool $asActiveQuery
	 * @return \yii\db\ActiveQuery|\yii\db\ActiveRecord|User|ProfileForm
	 * @throws NotFoundHttpException if no model was found
	 */
	protected function findModel($id, $modelName = null, $asActiveQuery = false)
	{
		$modelName = class_exists($modelName) ? $modelName : User::class;
		$query = $modelName::find()->andWhere([
			'id' => $id,
		]);

		if ($asActiveQuery) {
			if (!$query->count()) {
				throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
			}
			return $query;
		}

		$query->andWhere(['deleted' => User::NO]);
		if (($model = $query->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
