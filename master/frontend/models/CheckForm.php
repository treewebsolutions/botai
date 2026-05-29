<?php

namespace frontend\models;

use common\models\Workspace;
use Yii;
use yii\base\Model;
use yii\db\Query;

class CheckForm extends Model
{
	/**
	 * @var string $criterion The search criterion.
	 */
	public $criterion;

	/**
	 * @var string The honeypot field.
	 */
	public $workEmail;

    /**
     * @var string The honeypot field.
     */
    public $captchaResponse;


	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['criterion'], 'required'],
			[['criterion'], 'string', 'max' => 255],
			[['criterion'], 'trim'],
			['workEmail', 'safe'],
            ['captchaResponse', 'safe'],
        ];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'criterion' => Yii::t('label', 'Phone'),
		];
	}

	/**
	 * Checks if a workspace is eligible to display data.
	 *
	 * @param Workspace $workspace
	 * @return bool
	 * @throws \yii\db\Exception
	 * @throws \yii\base\InvalidConfigException
	 */
	protected function isWorkspaceEligibleToDisplayData($workspace)
	{
		return (bool) (new Query)
			->select(['COUNT([[si.id]]) AS [[id]]'])
			->from(['si' => '{{%service_input}}'])
			->leftJoin(['c' => '{{%client}}'], '[[c.id]] = [[si.client_id]]')
			->andWhere([
				'OR',
				['=', 'c.mobile_phone', $this->criterion],
				['=', 'c.fixed_phone', $this->criterion],
			])
			->createCommand($workspace->getWorkspaceDb())
			->queryScalar();
	}

	/**
	 * Searches the ServiceInput history.
	 *
	 * @return array
	 */
	public function search()
	{
		try {
			if (!empty($this->workEmail)) {
				$this->addError('', Yii::t('yii', 'Unable to verify your data submission.'));
				throw new \Exception();
			}
            if (Yii::$app->settings->get('reCaptchaSiteKey', 'general')) {
                if (!empty($this->captchaResponse)) {
                    $result = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . Yii::$app->settings->get('reCaptchaSecretKey', 'general') .'&response=' . $this->captchaResponse);
                    $response = json_decode($result);
                    if (empty($response->success)) {
                        return false;
                    }
                }
            }
			$dataset = [];
			$workspaces = Workspace::find()->andWhere(['deleted' => Workspace::NO]);

			/** @var Workspace $workspace */
			foreach ($workspaces->each() as $workspace) {
				if ($this->isWorkspaceEligibleToDisplayData($workspace)) {
					$dataset[] = $workspace;
				}
			}

			if (empty($dataset)) {
				$this->addError('criterion', Yii::t('yii', 'No results found.'));
			}

			return $dataset;
		} catch (\Exception $e) {
			return [];
		}
	}
}
