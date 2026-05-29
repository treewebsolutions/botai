<?php

namespace frontend\models;

use Yii;
use yii\base\Model;
use yii\web\Cookie;

class AcceptCookiesForm extends Model
{
    /**
     * @var string Accept Cookies field.
     */
    public $acceptCookies;

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
            [['acceptCookies'], 'required'],
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
            'name' => 'acceptCookies',
            'value' => $this->acceptCookies,
            'expire' => time() + 86400 * 365,
        ]);
        Yii::$app->getResponse()->getCookies()->add($cookie);
        return true;
    }
}
