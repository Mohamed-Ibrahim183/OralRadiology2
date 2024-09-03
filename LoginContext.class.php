<?php

require_once('LoginStrategy.php');

class LoginContext
{
	private $loginStrategy;

	public function setLoginStrategy(LoginStrategy $loginStrategy)
	{
		$this->loginStrategy = $loginStrategy;
	}

	public function executeLogin($identifier, $password)
	{
		return $this->loginStrategy->login($identifier, $password);
	}
}
