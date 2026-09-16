<?php

namespace tests\unit;

use common\models\LoginForm;
use common\models\ResetPasswordRequestForm;
use common\models\User;
use tests\DatabaseTestCase;

/**
 * The email address is the only credential the platform accepts.
 *
 * It used to accept three: `username`, `email` and `phone` all matched in the same
 * OR, even though only `email` is unique in the schema and only `email` is ever
 * verified. That let one person be named by three different strings, gave the login
 * form a value no validator could describe, and left it to whichever row the database
 * returned first to decide who was signed in.
 *
 * These tests pin the narrowed contract: the address logs you in, and the two columns
 * that used to double as credentials no longer do - even though they still hold data.
 */
class EmailOnlyAuthenticationTest extends DatabaseTestCase
{
	const PASSWORD = 'Str0ng-Test-Passphrase!';

	/**
	 * A user whose `username` and `phone` are populated and distinct from the address,
	 * so a lookup that still matched them would be visible.
	 *
	 * @return User
	 */
	private function createUser($email = 'owner@example.test')
	{
		$user = new User();
		$user->email = $email;
		$user->username = 'legacy.handle';
		$user->phone = '+40700111222';
		$user->first_name = 'Test';
		$user->last_name = 'Owner';
		$user->status = User::STATUS_ACTIVE;
		$user->deleted = User::NO;
		$user->setPassword(static::PASSWORD);
		$user->generateAuthKey();
		$this->assertTrue($user->save(false), 'fixture user could not be saved');

		return $user;
	}

	/**
	 * @param string $identifier
	 * @return LoginForm
	 */
	private function loginForm($identifier, $password = self::PASSWORD)
	{
		$form = new LoginForm();
		$form->email = $identifier;
		$form->password = $password;

		return $form;
	}

	public function testTheAddressFindsTheAccount()
	{
		$user = $this->createUser();

		$found = User::findByEmail('owner@example.test');
		$found = is_array($found) ? reset($found) : $found;

		$this->assertNotFalse($found);
		$this->assertSame($user->id, $found->id);
	}

	public function testTheLegacyUsernameColumnFindsNothing()
	{
		$this->createUser();

		$found = User::findByEmail('legacy.handle');

		$this->assertEmpty(is_array($found) ? $found : [$found]);
	}

	public function testThePhoneNumberFindsNothing()
	{
		$this->createUser();

		$found = User::findByEmail('+40700111222');

		$this->assertEmpty(is_array($found) ? $found : [$found]);
	}

	public function testTheAddressAndPasswordAuthenticate()
	{
		$this->createUser();

		$this->assertTrue($this->loginForm('owner@example.test')->validate());
	}

	public function testTheUsernameAndTheRightPasswordDoNotAuthenticate()
	{
		$this->createUser();

		$form = $this->loginForm('legacy.handle');
		$this->assertFalse($form->validate());
	}

	public function testThePhoneNumberAndTheRightPasswordDoNotAuthenticate()
	{
		$this->createUser();

		$form = $this->loginForm('+40700111222');
		$this->assertFalse($form->validate());
	}

	/**
	 * A value that is not an address is turned away by the form rather than carried
	 * into a query, and the rejection names the field - the old `safe` rule could not.
	 */
	public function testAValueThatIsNotAnAddressIsRejectedAsSuch()
	{
		$form = $this->loginForm('legacy.handle');

		$this->assertFalse($form->validate());
		$this->assertArrayHasKey('email', $form->getErrors());
	}

	public function testTheRightAddressWithTheWrongPasswordStillFails()
	{
		$this->createUser();

		$this->assertFalse($this->loginForm('owner@example.test', 'not-the-password')->validate());
	}

	/**
	 * The reset form answers the same way for a known and an unknown address, so it
	 * cannot be used to enumerate accounts - but it still refuses a value that is not
	 * an address at all.
	 */
	public function testThePasswordResetRequestOnlyAcceptsAnAddress()
	{
		$form = new ResetPasswordRequestForm();
		$form->email = 'legacy.handle';

		$this->assertFalse($form->validate());
		$this->assertArrayHasKey('email', $form->getErrors());
	}

	/**
	 * Two accounts may now share a phone number; only the address has to be free.
	 * The unique rule used to cover `phone`, which rejected the second signup and told
	 * the caller the number was already registered here.
	 */
	public function testTwoAccountsMayShareAPhoneNumber()
	{
		$this->createUser('first@example.test');

		$second = new User();
		$second->email = 'second@example.test';
		$second->phone = '+40700111222';
		$second->first_name = 'Second';
		$second->last_name = 'Owner';
		$second->status = User::STATUS_ACTIVE;
		$second->deleted = User::NO;
		$second->setPassword(static::PASSWORD);
		$second->generateAuthKey();

		$this->assertTrue($second->validate(), implode('; ', $second->getErrorSummary(true)));
	}

	public function testAnAddressIsStillTakenOnlyOnce()
	{
		$this->createUser('taken@example.test');

		$duplicate = new User();
		$duplicate->email = 'taken@example.test';
		$duplicate->first_name = 'Duplicate';
		$duplicate->last_name = 'Owner';
		$duplicate->status = User::STATUS_ACTIVE;
		$duplicate->deleted = User::NO;

		$this->assertFalse($duplicate->validate());
		$this->assertArrayHasKey('email', $duplicate->getErrors());
	}
}
