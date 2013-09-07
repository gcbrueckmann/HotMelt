<?php
namespace HotMelt\Middleware;

class BasicAccessAuthentication extends \HotMelt\Middleware
{
	const REQUIRES_AUTHENTICATION = 'hotmelt.middleware.basicaccessauthentication.options.requires_authentication';
	
	public function __construct($realm, $authenticator, $alwaysRequireAuthentication = false)
	{
		$this->realm = $realm;
		$this->authenticator = $authenticator;
		$this->alwaysRequireAuthentication = $alwaysRequireAuthentication;
	}
	
	private function checkCredentials($user, $password)
	{
		if (is_array($this->authenticator)) {
			foreach ($this->authenticator as $validUser => $validPassword) {
				if ($user == $validUser && $password == $validPassword) {
					return true;
				}
			}
			return false;
		} elseif (is_callable($this->authenticator)) {
			return call_user_func($this->authenticator, $user, $password);
		}
		throw new Exception('Invalid authenticator');
	}
	
	public function preAction($action, $request, $route)
	{
		if ($this->alwaysRequireAuthentication || $route->option(self::REQUIRES_AUTHENTICATION)) {
			if (!isset($_SERVER['PHP_AUTH_USER'])) {
				throw new \HotMelt\HTTPErrorException(401, $request->requestURI.' requires authentication.', array('WWW-Authenticate' => 'Basic realm="'.$this->realm.'"'));
			} elseif (!$this->checkCredentials($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
				throw new \HotMelt\HTTPErrorException(403, 'Invalid credentials.', array('WWW-Authenticate' => 'Basic realm="'.$this->realm.'"'));
			}
		}
		return array();
	}
	
	public function register()
	{
		\HotMelt\Middleware::registerHook('preAction', array($this, 'preAction'));
	}
}