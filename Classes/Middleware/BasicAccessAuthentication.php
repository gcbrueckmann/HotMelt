<?php
/**
 * Defines the `\HotMelt\Middleware\BasicAccessAuthentication` class.
 */
namespace HotMelt\Middleware;

/**
 * Provides HTTP Basic Access Authentication.
 * 
 * Authentication requirements can be specified on a per-route basis by setting the route option `\HotMelt\Middleware\BasicAccessAuthentication::REQUIRES_AUTHENTICATION`.
 */
class BasicAccessAuthentication extends \HotMelt\Middleware
{
	/**
	 * Route option indicating whether registered HTTP Basic Access Authentication middleware should require authentication.
	 * Set to `true` to require authentication.
	 */
	const REQUIRES_AUTHENTICATION = 'hotmelt.middleware.basicaccessauthentication.options.requires_authentication';
	
	/**
	 * Initialize an HTTP Basic Access Authentication middleware component.
	 * 
	 * @param string $realm The name of the realm.
	 * @param mixed $authenticator Either a keyed array where the keys are user names and the values are matching passwords, or a callback function taking a user name and password as arguments. Must return `true`, if these are valid or `false`, if they are not..
	 * @param boolean $alwaysRequireAuthentication A boolean indicating if the middleware component should require authentication even if the route options do not specify any such requirement.
	 */
	public function __construct($realm, $authenticator, $alwaysRequireAuthentication = false)
	{
		$this->realm = $realm;
		$this->authenticator = $authenticator;
		$this->alwaysRequireAuthentication = $alwaysRequireAuthentication;
	}
	
	/**
	 * Consults the middleware components authenticator to validate a user name and password combination.
	 * 
	 * @param string $user A user name.
	 * @param string $password A password.
	 * @return boolean `true` if the given user name and password combination is valid or `false`, if it is not.
	 */
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
	
	/** @ignore */
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
	
	/** @ignore */
	public function register()
	{
		\HotMelt\Middleware::registerHook('preAction', array($this, 'preAction'));
	}
}