<?php

namespace tests\unit;

use common\models\LoginForm;
use common\models\PasswordResetRequestForm;
use common\models\User;
use tests\DatabaseTestCase;
use Yii;

/**
 * A tenant accepts the same single credential the hub does: the email address.
 *
 * The lookup's name and docblock still promised "username, email or phone" while the
 * query had already been narrowed to the address, so the three apps disagreed about
 * what a login is. This pins the tenant side of the contract.
 *
 * Rows are inserted with raw SQL on purpose: saving a tenant User pushes a copy to the
 * hub through master\User::createModel(), which needs a signed-in identity and a
 * reachable masterDb - neither of which a unit test has, and neither of which the
 * lookup under test touches.
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
	 * The reset form answers a known and an unknown address the same way. It used to
	 * reject the unknown one with "There is no user with this email address", which
	 * made it a membership oracle - the hub's equivalent had already been fixed, so
	 * the two apps disagreed about what a reset request may reveal.
	 */
	public function testThePasswordResetRequestDoesNotRevealWhoHasAnAccount()
	{
		$this->insertUser('known@example.test');

		$known = new PasswordResetRequestForm();
		$known->email = 'known@example.test';
		$unknown = new PasswordResetRequestForm();
		$unknown->email = 'stranger@example.test';

		$this->assertTrue($known->validate());
		$this->assertTrue($unknown->validate(), 'an unknown address must not be rejected differently');
		$this->assertSame($known->getErrors(), $unknown->getErrors());
	}

	public function testThePasswordResetRequestStillRefusesAValueThatIsNotAnAddress()
	{
		$form = new PasswordResetRequestForm();
		$form->email = 'legacy.handle';

		$this->assertFalse($form->validate());
		$this->assertArrayHasKey('email', $form->getErrors());
	}

	/**
	 * The autofill behind the tenant's user form called a method this model never
	 * defined, so every lookup ended in "call to undefined method".
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
