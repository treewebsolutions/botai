<?php

namespace backend\modules\setting\modules\language\controllers;

use backend\controllers\MainController;
use backend\modules\setting\modules\language\assets\LanguageTranslateAsset;
use backend\modules\setting\modules\language\models\LanguageSearch;
use backend\modules\setting\modules\language\models\LanguageTranslationSearch;
use common\models\Language;
use common\models\LanguageSource;
use common\models\LanguageTranslate;
use Yii;
use yii\db\ActiveQuery;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class LanguageController extends MainController
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
						'actions' => ['index', 'dt-languages'],
						'roles' => ['viewLanguage'],
					],
					[
						'allow' => true,
						'actions' => ['translate', 'dt-language-translations'],
						'roles' => ['translateIntoLanguage'],
					],
				],
			],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [
			'dt-languages' => LanguageSearch::class,
			'dt-language-translations' => LanguageTranslationSearch::class,
		];
	}

	/**
	 * Lists all Language models.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	public function actionIndex()
	{
		if (Yii::$app->request->isAjax && Yii::$app->request->post('dt')) {
			return $this->updateDtColumn();
		}

		return $this->render('index');
	}

	/**
	 * Lists all LanguageSource models.
	 *
	 * @param string $id
	 * @return mixed
	 */
	public function actionTranslate($id)
	{
		LanguageTranslateAsset::register($this->view);

		if (Yii::$app->request->isAjax && Yii::$app->request->post('dt')) {
			return $this->saveTranslation($id);
		}

		$separators = [
			'af-ZA' => '',
			'ar-AR' => '',
			'az-AZ' => '',
			'be-BY' => ' :; ',
			'bg-BG' => ' ;; ',
			'bn-IN' => '',
			'bs-BA' => ' :; ',
			'ca-ES' => '',
			'cs-CZ' => ' :; ',
			'cy-GB' => '',
			'da-DK' => ' :; ',
			'de-DE' => ' ;; ',
			'el-GR' => ' :; ',
			'en-GB' => '',
			'en-PI' => '',
			'en-UD' => '',
			'en-US' => '',
			'eo-EO' => '',
			'es-ES' => ' ___ ',
			'es-LA' => ' ___ ',
			'et-EE' => ' :; ',
			'eu-ES' => '',
			'fa-IR' => '',
			'fi-FI' => ' ;; ',
			'fr-CA' => ' ___ ',
			'fr-FR' => ' ___ ',
			'fy-NL' => '',
			'ga-IE' => '',
			'gl-ES' => '',
			'he-IL' => '',
			'hi-IN' => '',
			'hr-HR' => ' :; ',
			'hu-HU' => ' ;; ',
			'hy-AM' => '',
			'id-ID' => '',
			'is-IS' => '',
			'it-IT' => ' ___ ',
			'ja-JP' => '',
			'ka-GE' => '',
			'km-KH' => '',
			'ko-KR' => '',
			'ku-TR' => '',
			'lt-LT' => ' :; ',
			'lv-LV' => ' :; ',
			'mk-MK' => ' :; ',
			'ml-IN' => '',
			'ms-MY' => '',
			'ne-NP' => '',
			'nl-NL' => ' :; ',
			'pa-IN' => '',
			'pl-PL' => ' ;; ',
			'ps-AF' => '',
			'pt-BR' => ' ___ ',
			'pt-PT' => ' ___ ',
			'ro-RO' => ' .∞. ',
			'ru-RU' => ' :; ',
			'sk-SK' => ' :; ',
			'sl-SI' => ' :; ',
			'sq-AL' => ' :; ',
			'sr-RS' => ' :; ',
			'sv-SE' => ' .∞. ',
			'sw-KE' => '',
			'ta-IN' => '',
			'te-IN' => '',
			'th-TH' => '',
			'tl-PH' => '',
			'tr-TR' => ' :; ',
			'uk-UA' => ' :; ',
			'vi-VN' => '',
			'zh-CN' => '',
			'zh-HK' => '',
			'zh-TW' => '',
		];

		if (Yii::$app->request->get('translator')) {
			$records = LanguageSource::find()
				->alias('ls')
				->joinWith([
					'languageTranslates lt' => function (ActiveQuery $query) {
						$query->andOnCondition(['lt.language' => Yii::$app->request->get('id')]);
					},
				])
				->asArray()
				->all();
			$counter = count($records);
			$source = Language::findOne(['language_id' => 'en-US'])->language;
			$target = Language::findOne(['language_id' => Yii::$app->request->get('id')])->language;

			// Use unique separator that's unlikely to appear in translations
			$uniqueSeparator = '###TRANS_SEP_' . uniqid() . '###';
			$separator = $separators[Yii::$app->request->get('id')] ?: $uniqueSeparator;

			// If the language has no specific separator, use the unique one
			if (empty($separator)) {
				$separator = $uniqueSeparator;
			}

			$limit = 5000;
			$batches = [];
			$batchRecords = [];
			$currentBatch = '';
			$batchIndex = 0;

			// Group records into batches
			foreach ($records as $index => $record) {
				$message = $record['message'];
				$testLength = strlen($currentBatch) + strlen($message) + strlen($separator);

				if ($testLength <= $limit && !empty($currentBatch)) {
					$currentBatch .= $separator . $message;
					$batchRecords[$batchIndex][] = $index;
				} else {
					if (!empty($currentBatch)) {
						$batches[] = $currentBatch;
						$batchIndex++;
					}
					$currentBatch = $message;
					$batchRecords[$batchIndex] = [$index];
				}
			}
			// Add the last batch
			if (!empty($currentBatch)) {
				$batches[] = $currentBatch;
			}

			// Translate batches
			$translations = array_fill(0, $counter, '');
			foreach ($batches as $batchIdx => $batchText) {
				try {
					$translatedBatch = Yii::$app->translate->translate($source, $target, $batchText)['data']['translations'][0]['translatedText'];

					// Ensure UTF-8 encoding
					if (!mb_check_encoding($translatedBatch, 'UTF-8')) {
						$translatedBatch = mb_convert_encoding($translatedBatch, 'UTF-8', 'UTF-8');
					}

					// Split translated batch
					$batchTranslations = explode($separator, $translatedBatch);

					// Map translations back to original records
					foreach ($batchRecords[$batchIdx] as $position => $recordIndex) {
						if (isset($batchTranslations[$position])) {
							$translations[$recordIndex] = trim($batchTranslations[$position]);
						} else {
							// Fallback to original if split failed
							$translations[$recordIndex] = $records[$recordIndex]['message'];
						}
					}
				} catch (\Exception $e) {
					// If batch translation fails, use original messages
					foreach ($batchRecords[$batchIdx] as $recordIndex) {
						$translations[$recordIndex] = $records[$recordIndex]['message'];
					}
				}
			}

			if (count($translations) == $counter) {
				try {
					$translated = LanguageSource::find()
						->alias('ls')
						->joinWith([
							'languageTranslates lt' => function (ActiveQuery $query) {
								$query->andOnCondition(['lt.language' => Yii::$app->request->get('id')]);
							},
						])
						->where([
							'lt.language' => Yii::$app->request->get('id'),
						])
						->andWhere([
							'AND',
							['IS NOT', 'lt.translation', null],
							['NOT IN', 'lt.translation', ['', ' ']],
						])
						->asArray()
						->all();
					for ($i = 0; $i < $counter; $i++) {
						if (!empty($translated) && in_array($records[$i]['id'], ArrayHelper::getColumn($translated, 'id'))) {
							$model = LanguageTranslate::findOne(['id' => $records[$i]['id']]);
						} else {
							$model = new LanguageTranslate();
						}
						if ($model->id && !Yii::$app->request->get('overwrite')) {
							continue;
						} else {
							$model->id = $records[$i]['id'];
							$model->language = Yii::$app->request->get('id');
							$model->translation = $translations[$i];
							if (!$model->save()) {
								throw new \Exception(Yii::t('common', 'Translation failed.'));
							}
						}
					}
					$message = Yii::t('common', 'Translation successful.');
					Yii::$app->session->setFlash('success', $message);
					return $this->redirect(['translate', 'id' => Yii::$app->request->get('id')]);
				} catch (\Exception $e) {
					Yii::$app->session->setFlash('error', $e->getMessage());
					return $this->redirect(['translate', 'id' => Yii::$app->request->get('id')]);
				}
			} else {
				Yii::$app->session->setFlash('error', Yii::t('common', 'Translation count mismatch. Expected {expected}, got {actual}.', [
					'expected' => $counter,
					'actual' => count($translations),
				]));
				return $this->redirect(['translate', 'id' => Yii::$app->request->get('id')]);
			}
		}

		return $this->render('translate', [
			'categories' => ArrayHelper::map(LanguageSource::find()->all(), 'category', 'category'),
			'separators' => $separators,
		]);
	}

	/**
	 * Finds the Language model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @return Language the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id)
	{
		if (($model = Language::findOne($id)) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}

	/**
	 * Updates DataTable column.
	 *
	 * @return \yii\web\Response
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function updateDtColumn()
	{
		$params = Yii::$app->request->post();
		$result = false;

		$model = $this->findModel($params['key']);

		Yii::$app->eventLog
			->setData([
				'operation' => (Yii::$app->eventLog)::ACTION_UPDATE,
			])
			->beginRecord($model);

		if ($model->hasAttribute($params['attribute'])) {
			$model->{$params['attribute']} = $params['value'];

			if ($result = $model->save(true, [$params['attribute']])) {
				Yii::$app->eventLog->endRecord();
			}
		}

		Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllLanguages']));

		return $this->asJson([
			'success' => (bool) $result,
			'message' => $result ?
				Yii::t('common', 'Record has been updated.') :
				Yii::t('common', 'Cannot update the record.'),
		]);
	}

	/**
	 * Updates translation column.
	 *
	 * @param string $language_id
	 * @return \yii\web\Response
	 */
	protected function saveTranslation($language_id)
	{
		$params = Yii::$app->request->post();

		$model = LanguageTranslate::findOne([
			'id' => $params['key'] ?? null,
			'language' => $language_id,
		]);
		if (!$model) {
			$model = new LanguageTranslate();
			$model->id = $params['key'] ?? null;
			$model->language = $language_id;
		}

		$result = false;
		if ($model->hasAttribute($params['attribute'] ?? '')) {
			// Ensure UTF-8 encoding to prevent JSON errors
			$value = $params['value'] ?? '';
			if (!mb_check_encoding($value, 'UTF-8')) {
				$value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
			}
			$model->{$params['attribute']} = $value;
			$result = $model->save();
			Yii::$app->trigger('invalidate.cache');
		}

		return $this->asJson([
			'success' => (bool) $result,
			'message' => $result ?
				Yii::t('common', 'Record has been updated.') :
				Yii::t('common', 'Cannot update the record.'),
		]);
	}
}
