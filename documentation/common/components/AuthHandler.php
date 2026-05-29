<?php
namespace common\components;

use common\models\Auth;
use common\models\User;
use Yii;
use yii\authclient\ClientInterface;
use yii\helpers\ArrayHelper;

/**
 * AuthHandler handles successful authentification via Yii auth component
 */
class AuthHandler
{
    /**
     * @var ClientInterface
     */
    private $client;

    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
    }

    public function handle()
    {
        try {
            $attributes = $this->client->getUserAttributes();
            $id = ArrayHelper::getValue($attributes, 'id');
            $email = ArrayHelper::getValue($attributes, 'email');
            if ($this->client->name == 'google') {
                $first_name = ArrayHelper::getValue($attributes, 'given_name');
                $last_name = ArrayHelper::getValue($attributes, 'family_name');
            } else if ($this->client->name == 'facebook') {
                $first_name = ArrayHelper::getValue($attributes, 'first_name');
                $last_name = ArrayHelper::getValue($attributes, 'last_name');
            }

            /** @var Auth $auth */
            $auth = Auth::find()->where([
                'source' => $this->client->getId(),
                'source_id' => $id,
            ])->one();

            if (Yii::$app->user->isGuest) {
                if ($auth) { // login
                    /** @var User $user */
                    $user = $auth->user;
                    Yii::$app->user->login($user, Yii::$app->settings->get('userLoginDuration') ?: 0);
                } else { // signup
                    if ($email !== null && User::find()->where(['email' => $email])->exists()) {
                        Yii::$app->getSession()->setFlash('error', [
                            Yii::t('common', "User with the same email as in {client} account already exists but isn't linked to it. Login using email first to link it.", ['client' => $this->client->getTitle()]),
                        ]);
                    } else {
                        $password = Yii::$app->security->generateRandomString(6);
                        $user = new User();
                        $user->first_name = $first_name;
                        $user->last_name = $last_name;
                        $user->email = $email;
                        $user->status = User::STATUS_ACTIVE;
                        $user->setPassword($password);
                        $user->generateAuthKey();

                        $transaction = User::getDb()->beginTransaction();

                        if ($user->save()) {
                            $auth = new Auth([
                                'user_id' => $user->id,
                                'source' => $this->client->getId(),
                                'source_id' => (string)$id,
                            ]);
                            if ($auth->save()) {
	                            $transaction->commit();
	                            Yii::$app->user->login($user, Yii::$app->params['user.rememberMeDuration']);
                            } else {
                                $transaction->rollBack();
                                Yii::$app->getSession()->setFlash('error', [
                                    Yii::t('common', 'Unable to save {client} account: {errors}', [
                                        'client' => $this->client->getTitle(),
                                        'errors' => json_encode($auth->getErrors()),
                                    ]),
                                ]);
                            }
                        } else {
                            Yii::$app->getSession()->setFlash('error', [
                                Yii::t('common', 'Unable to save user: {errors}', [
                                    'client' => $this->client->getTitle(),
                                    'errors' => json_encode($user->getErrors()),
                                ]),
                            ]);
                        }
                    }
                }
            } else { // user already logged in
                if (!$auth) { // add auth provider
                    $auth = new Auth([
                        'user_id' => Yii::$app->user->id,
                        'source' => $this->client->getId(),
                        'source_id' => (string)$attributes['id'],
                    ]);
                    if ($auth->save()) {
                        /** @var User $user */
                        $user = $auth->user;
                        Yii::$app->getSession()->setFlash('success', [
                            Yii::t('common', 'Linked {client} account.', [
                                'client' => $this->client->getTitle()
                            ]),
                        ]);
                    } else {
                        Yii::$app->getSession()->setFlash('error', [
                            Yii::t('common', 'Unable to link {client} account: {errors}', [
                                'client' => $this->client->getTitle(),
                                'errors' => json_encode($auth->getErrors()),
                            ]),
                        ]);
                    }
                } else { // there's existing auth
                    Yii::$app->getSession()->setFlash('error', [
                        Yii::t('common',
                            'Unable to link {client} account. There is another user using it.',
                            ['client' => $this->client->getTitle()]),
                    ]);
                }
            }
            return true;
        } catch(\Exception $e) {
            return false;
        }
    }
}