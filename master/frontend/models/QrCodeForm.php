<?php

namespace frontend\models;

use common\models\Contractor;
use Da\QrCode\Contracts\ErrorCorrectionLevelInterface;
use Da\QrCode\Label;
use Da\QrCode\QrCode;
use tws\helpers\Url;
use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;

class QrCodeForm extends Model
{
	public $code;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code'], 'required'],
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
     * Generate qr code.
     *
     * @return bool whether the email was sent.
     */
    public function generate()
    {
	    $contractor = Contractor::findOne(['code' => $this->code]);
		if ($contractor) {
			$dirPath = Yii::getAlias("@uploads/qr-code/{$contractor->id}");
			$fileName = "{$this->code}.png";
			$filePath = "{$dirPath}/{$fileName}";
			FileHelper::createDirectory($dirPath);

			$label = (new Label($contractor->name))
				->setFont(__DIR__ . '/../web/fonts/gardenia/Gardenia-Regular.woff')
				->setFontSize(16);
			$qrCode = (new QrCode($this->code))
				->setLogo(__DIR__ . '/../web/img/logo-symbol.png')
				->setForegroundColor(1, 91, 102)
				->setBackgroundColor(255, 255, 255)
				->setEncoding('UTF-8')
				->setErrorCorrectionLevel(ErrorCorrectionLevelInterface::HIGH)
				->setLogoWidth(100)
				->setSize(500)
				->setMargin(10)
				->setLabel($label);
			$qrCode->writeFile($filePath);

			if (is_file($filePath)) {
				return Url::to($filePath);
			}
		}
		return null;
    }
}
