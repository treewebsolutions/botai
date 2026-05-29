<?php

namespace frontend\modules\embed\models;

use yii\base\Model;

class ChatForm extends Model
{
	/**
	 * @var string The honeypot field.
	 */
	public $verifyCode;


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			['verifyCode', 'safe'],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
		];
	}

	/**
	 * Searches the input history.
	 *
	 * @return bool|array
	 */
	public function message()
	{
		$data = [];

		if (empty($data)) {
			return false;
		}

		return $data;
	}
}
