<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\web\Cookie;

class SearchForm extends Model
{
    /**
     * @var string Accept Cookies field.
     */
    public $search;

    /**
     * @var string Back Url field.
     */
    public $backUrl;

    /**
     * @var string The honeypot field.
     */
    public $workEmail;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['search'], 'safe'],
            ['backUrl', 'safe'],
            ['workEmail', 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [];
    }

    /**
     * Sets accept cookies.
     *
     * @return bool whether the email was sent.
     */
    public function setCookies()
    {
        $cookie = new Cookie([
            'name' => 'search',
            'value' => $this->search,
            'expire' => time() + 86400 * 365,
        ]);
        Yii::$app->getResponse()->getCookies()->add($cookie);
        return true;
    }
}
