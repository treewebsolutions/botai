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
						'roles' => ['viewLanguageSetting'],
					],
					[
						'allow' => true,
						'actions' => ['translate', 'dt-language-translations'],
						'roles' => ['translateIntoLanguageSetting'],
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

            $separator = $separators[Yii::$app->request->get('id')];
            $limit = 5000;
            $results = [];
            $key = 0;
            $w = 0;
            $s = 0;
            $translation = [];
            do {
                if (strlen((string)$results[$key]) + strlen((string)$records[$w]['message']) + strlen($separator) <= $limit) {
                    $results[$key] .= (strlen((string)$results[$key]) ? $separator : '');
                    $results[$key] .= $records[$w]['message'];
                    $s++;
                } else {
                    $results[] = $records[$w]['message'];
                    $key++;
                    $s = 0;
                }
                $w++;
            } while ($w < $counter);

            foreach ($results as $result) {
                $translation[] = Yii::$app->translate->translate($source, $target, $result)['data']['translations'][0]['translatedText'];
            }

            if (!$separator) {
                Yii::$app->session->setFlash('error', Yii::t('common', 'Translation failed.'));
                return $this->redirect(['translate', 'id' => Yii::$app->request->get('id')]);
            }

            $translations = explode(trim($separator), implode(trim($separator), $translation));

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
                Yii::$app->session->setFlash('error', Yii::t('common', 'Translation failed.'));
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
			'id' => $params['key'],
			'language' => $language_id,
		]);
		if (!$model) {
			$model = new LanguageTranslate();
			$model->id = $params['key'];
			$model->language = $language_id;
		}

		$result = false;
		if ($model->hasAttribute($params['attribute'])) {
			$model->{$params['attribute']} = $params['value'];
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
