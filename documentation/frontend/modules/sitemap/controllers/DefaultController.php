<?php

namespace frontend\modules\sitemap\controllers;

use common\models\Article;
use common\models\ArticleCategory;
use common\models\Page;
use common\models\PageTranslation;
use common\models\Product;
use common\models\ProductCategory;
use frontend\controllers\MainController;
use tws\widgets\sitemap\Sitemap;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use yii\helpers\Inflector;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class DefaultController extends MainController
{

	private static function toXml($array)
	{
		$xml = '';
		if (is_array($array)) {
			foreach ($array as $key => $value) {
				if (is_numeric($key)) {
					$key = "element";
				}
				if (is_array($value)) {
					$xml .= "<$key>" . static::toXml($value) . "</$key>";
				} elseif (strlen(trim($value)) === 0) {
					$xml .= "<$key/>";
				} else {
					$xml .= "<$key>" . htmlspecialchars($value) . "</$key>";
				}
			}
		}
		$xml = str_replace(['<element>', '</element>'], ['', ''], $xml);
		return $xml;
	}


	/**
	 * Displays index view.
	 *
	 * @return mixed
	 */
	public function actionIndex()
	{
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_RAW;

        $headers = $response->getHeaders();
        $headers->set('Content-Type', 'application/xml; charset=utf-8');

        $dataProvider[0]['sitemap'] = [
			'loc' => implode('', array_filter([
				\tws\helpers\Url::base(true),
				(Yii::$app->settings->get('defaultLanguage') == Yii::$app->language ? '' : '/' . (in_array((mb_substr(Yii::$app->language, 0, 2)), ['en']) && !in_array(Yii::$app->language, ['en-US']) ? Yii::$app->language : (mb_substr(Yii::$app->language, 0, 2)))),
				'/sitemap0',
			])),
		];

		$xmlData = static::toXml($dataProvider);
		$xml = implode('', [
			'<?xml version="1.0" encoding="UTF-8"?>',
			'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
			$xmlData,
			'</sitemapindex>'
		]);
		return $xml;
	}

	/**
	 * Displays a single Article model.
	 *
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionView()
    {
        $response = Yii::$app->getResponse();
        $response->format = Response::FORMAT_RAW;

        $headers = $response->getHeaders();
        $headers->set('Content-Type', 'application/xml; charset=utf-8');

        if (Yii::$app->request->get('index') == 0) {
            $pagesDataProvider = new ActiveDataProvider([
                'query' => Page::find()
	                ->alias('p')
	                ->select([
		                'p.*',
	                ])
	                ->joinWith([
		                'pageTranslations pt' => function (ActiveQuery $query) {
			                return $query->andOnCondition([
				                'pt.language_id' => Yii::$app->language,
				                'pt.deleted' => PageTranslation::NO,
			                ]);
		                },
	                ])
                    ->andWhere([
                        'OR',
                        ['IS', 'p.module', null],
                        ['!=', 'p.module', 'account'],
                    ])
	                ->andWhere([
		                'OR',
		                ['=', 'p.default', Page::YES],
		                ['=', 'pt.language_id', Yii::$app->language],
	                ])
                    ->andWhere(['NOT IN', 'p.action', ['subscribe', 'unsuscribe']])
                    ->andWhere([
                        'p.status' => Page::STATUS_ACTIVE,
                        'p.deleted' => Page::NO,
                    ]),
                'pagination' => [
                    'pageSize' => 0,
                ],
            ]);

            return Sitemap::widget([
                'items' => [
                    [
                        'dataProvider' => $pagesDataProvider,
                        'route' => function ($item, $model) {
                            return implode('/', array_filter([
                                $model->translation->slug
                            ]));
                        },
                        'options' => [
                            'changefreq' => Sitemap::CHANGEFREQ_YEARLY,
                            'priority' => '1.0',
                        ],
                    ],
                ],
            ]);
        }
    }
}
