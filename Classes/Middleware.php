<?php
/**
 * Defines the `\HotMelt\Middleware` class.
 */
namespace HotMelt;

/**
 * Provides an API for extending HotMelt's request handling machinery.
 * 
 * Middleware can register for hooks to intercept requests.
 */
class Middleware
{
	/**
	 * Initialize a middleware instance.
	 * 
	 * The default implementation does not take any arguments.
	 */
	public function __construct()
	{
		// No-op, but required so that we can pass an arguments array to ReflectionClass->newInstanceArgs().
	}
	
	/**
	 * Loads middleware definitions from the site directory.
	 * 
	 * @return void
	 */
	private static function registerMiddleware()
	{
		$siteMiddlewarePath = dirname(__FILE__).'/../../Site/middleware.php';
		if (file_exists($siteMiddlewarePath)) {
			require_once($siteMiddlewarePath);
		}
	}
	
	/** @ignore */
	private static $hooks;
	
	/**
	 * Registers a callback function for a hook.
	 * 
	 * You must only call this function from within the `register()` method of your middleware subclass.
	 * Strictly speaking the middleware hook facility is not limited to subclasses of `\HotMelt\Middleware`.
	 * Note, however, that registering a callback through this function in any other context than the `register()` method is not officially supported.
	 * 
	 * @param string $name The hook to register for.
	 * @param callable $callback The callback to execute for the hook.
	 * @return void
	 */
	public static function registerHook($name, $callback)
	{
		if (!isset(self::$hooks[$name])) {
			self::$hooks[$name] = array();
		}
		self::$hooks[$name][] = $callback;
	}
	
	/**
	 * Executes all callbacks registered for a given hook.
	 * 
	 * Additional parameters passed to this method are passed on to registered callbacks.
	 * 
	 * @param string $name The name of hook for which to execute registered callbacks.
	 * @return array The data accumulated by executing all registered callbacks.
	 */
	public static function executeHook($name)
	{
		self::registerMiddleware();
		
		$args = array_slice(func_get_args(), 1);
		$data = array();
		if (!is_array(self::$hooks[$name])) {
			Log::info("No observers registered for hook $name.");
		} else {
			foreach (self::$hooks[$name] as $observer) {
				$data = array_merge($data, call_user_func_array($observer, $args));
			}
		}
		return $data;
	}
	
	/**
	 * Add a middleware component by name.
	 * 
	 * Additional parameters passed to this method are passed on to the middleware constructor.
	 * 
	 * @param string $name The class name of the middleware to add.
	 */
	public static function add($name)
	{
		$args = array_slice(func_get_args(), 1);
		$reflector = new \ReflectionClass($name);
		$middleware = $reflector->newInstanceArgs($args);
		$middleware->register();
		return $middleware;
	}
}