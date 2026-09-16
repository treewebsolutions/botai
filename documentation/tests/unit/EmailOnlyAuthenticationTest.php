<?php

namespace tests\unit;

use common\models\LoginForm;
use common\models\User;
use tests\DatabaseTestCase;
use Yii;

/**
 * The documentation app accepts the same single credential as the hub and the tenants:
 * the email address.
 *
 * This app was the last one still matching `username` and `phone` in the same OR as
 * `email`. Only the address is unique in the schema, so a handle or a phone number
 * shared by two rows let whichever one the database returned first decide who was
 * signed in - and neither column is ever verified to belong to the person typing it.
 */
class EmailOnlyAuthenticationTest extends DatabaseTestCase
{
	const PASSWORD = 'Str0ng-Test-Passphrase!';

	/**
	 * @return int the new user's id
	 */
	private function insertUser($email = 'owner@example.test')
	{
		return $this->insertRow('user', [
			'email' => $email,
			'username' => 'legacy.handle',
			'phone' => '+40700111222',
			'first_name' => 'Test',
			'last_name' => 'Owner',
			'password_hash' => Yii::$app->security->generatePasswordHash(static::PASSWORD),
			'auth_key' => Yii::$app->security->generateRandomString(),
			'status' => User::STATUS_ACTIVE,
			'deleted' => User::NO,
		]);
	}

	public function testTheAddressFindsTheAccount()
	{
		$id = $this->insertUser();

		$found = User::findByEmail('owner@example.test');

		$this->assertNotNull($found);
		$this->assertSame((int) $id, (int) $found->id);
	}

	public function testTheLegacyUsernameColumnFindsNothing()
	{
		$this->insertUser();

		$this->assertNull(User::findByEmail('legacy.handle'));
	}

	public function testThePhoneNumberFindsNothing()
	{
		$this->insertUser();

		$this->assertNull(User::findByEmail('+40700111222'));
	}

	public function testTheAddressAndPasswordAuthenticate()
	{
		$this->insertUser();

		$form = new LoginForm();
		$form->email = 'owner@example.test';
		$form->password = static::PASSWORD;

		$this->assertTrue($form->validate());
	}

	public function testTheUsernameDoesNotAuthenticate()
	{
		$this->insertUser();

		$form = new LoginForm();
		$form->email = 'legacy.handle';
		$form->password = static::PASSWORD;

		$this->assertFalse($form->validate());
		$this->assertArrayHasKey('email', $form->getErrors());
	}

	public function testThePhoneNumberDoesNotAuthenticate()
	{
		$this->insertUser();

		$form = new LoginForm();
		$form->email = '+40700111222';
		$form->password = static::PASSWORD;

		$this->assertFalse($form->validate());
	}

	/**
	 * The backend autofill that looks up an existing person now resolves by address
	 * only, the same as the hub's.
	 */
	public function testTheUserFormAutofillResolvesByAddress()
	{
		$id = $this->insertUser('autofill@example.test');

		$found = User::findAllUsersWithoutRoleByEmail('autofill@example.test');

		$this->assertCount(1, $found);
		$this->assertSame((int) $id, (int) reset($found)->id);
		$this->assertSame([], User::findAllUsersWithoutRoleByEmail('legacy.handle'));
	}
}
