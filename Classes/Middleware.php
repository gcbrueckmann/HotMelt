<?php
namespace HotMelt;

class Middleware
{
	public function __construct()
	{
		// No-op, but required so that we can pass an arguments array to ReflectionClass->newInstanceArgs().
	}
	
	private static function registerMiddleware()
	{
		$siteMiddlewarePath = dirname(__FILE__).'/../../Site/middleware.php';
		if (file_exists($siteMiddlewarePath)) {
			require_once($siteMiddlewarePath);
		}
	}
	
	private static $hooks;
	
	public static function registerHook($name, $callback)
	{
		if (!isset(self::$hooks[$name])) {
			self::$hooks[$name] = array();
		}
		self::$hooks[$name][] = $callback;
	}
	
	public static function executeHook(/* $name, ... */)
	{
		self::registerMiddleware();
		
		$name = func_get_arg(0);
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
	
	public static function add(/* $name, ... */)
	{
		$name = func_get_arg(0);
		$args = array_slice(func_get_args(), 1);
		$reflector = new \ReflectionClass($name);
		$middleware = $reflector->newInstanceArgs($args);
		$middleware->register();
		return $middleware;
	}
}