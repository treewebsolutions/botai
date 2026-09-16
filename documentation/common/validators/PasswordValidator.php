<?php

namespace common\validators;

use Yii;
use yii\validators\Validator;

/**
 * The password policy, in one place.
 *
 * It was `['string', 'min' => 6]` repeated across six forms, and absent altogether on
 * the API's user form — six characters of anything, which is what the assessment picked
 * up. The rule now lives here so signup, reset, the profile screens and the API cannot
 * drift apart again.
 *
 * Length does most of the work, so the minimum is the part worth being strict about;
 * the character-class requirements are the assessment's, kept because they are what the
 * report asks for. There is deliberately no maximum: capping a password only shrinks
 * the search space, and the hash is fixed-length whatever goes in.
 */
class PasswordValidator extends Validator
{
	/**
	 * @var int The shortest password accepted.
	 */
	public $min = 10;

	/**
	 * @var bool Whether an uppercase letter is required.
	 */
	public $requireUppercase = true;

	/**
	 * @var bool Whether a lowercase letter is required.
	 */
	public $requireLowercase = true;

	/**
	 * @var bool Whether a digit is required.
	 */
	public $requireDigit = true;

	/**
	 * @var bool Whether a non-alphanumeric character is required.
	 */
	public $requireSpecial = true;

	/**
	 * {@inheritdoc}
	 */
	public $skipOnEmpty = true;

	/**
	 * {@inheritdoc}
	 */
	protected function validateValue($value)
	{
		if (!is_string($value)) {
			return [Yii::t('common', 'The password must be a string.'), []];
		}

		// Count characters, not bytes: a password of accented letters or emoji is not
		// shorter than it looks.
		if (mb_strlen($value) < $this->min) {
			return [
				Yii::t('common', 'The password must contain at least {min, number} characters.', ['min' => $this->min]),
				[],
			];
		}
		if ($this->requireUppercase && !preg_match('/\p{Lu}/u', $value)) {
			return [Yii::t('common', 'The password must contain at least one uppercase letter.'), []];
		}
		if ($this->requireLowercase && !preg_match('/\p{Ll}/u', $value)) {
			return [Yii::t('common', 'The password must contain at least one lowercase letter.'), []];
		}
		if ($this->requireDigit && !preg_match('/\d/u', $value)) {
			return [Yii::t('common', 'The password must contain at least one number.'), []];
		}
		if ($this->requireSpecial && !preg_match('/[^\p{L}\p{N}]/u', $value)) {
			return [Yii::t('common', 'The password must contain at least one special character.'), []];
		}

		return null;
	}
}
