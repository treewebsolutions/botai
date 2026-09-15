<?php

namespace tests\support;

/**
 * Test double for yii\web\Session: keeps the full Session API (flash messages
 * included) but never calls session_start()/session_set_cookie_params(), which
 * fail under PHPUnit once the progress output has been flushed ("headers have
 * already been sent" on the CLI SAPI).
 */
class FakeSession extends \yii\web\Session
{
	/**
	 * @var bool
	 */
	private $active = false;

	/**
	 * @inheritdoc
	 */
	public function open()
	{
		if (!isset($_SESSION)) {
			$_SESSION = [];
		}
		$this->active = true;
	}

	/**
	 * @inheritdoc
	 */
	public function close()
	{
		$this->active = false;
	}

	/**
	 * @inheritdoc
	 */
	public function getIsActive()
	{
		return $this->active;
	}

	/**
	 * @inheritdoc
	 */
	public function destroy()
	{
		$_SESSION = [];
	}
}
