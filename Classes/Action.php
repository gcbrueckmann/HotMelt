<?php
/**
 * Defines the `\HotMelt\Action` class.
 */
namespace HotMelt;

/**
 * Actions process a request and produce data to be displayed in a view.
 * 
 * @property-read callable $callable The callback function to execute for this action
 * @property-read array $args Additional parameters passed to the constructor.
 *   These parameters will be passed to the action's callable.
 */
class Action
{
	/**
	 * Initialize an action.
	 * 
	 * Additional parameters passed to the constructor are saved in the `args` property and passed on to `$callable`.
	 * 
	 * @param callable $callable A callback function to execute for this action.
	 */
	public function __construct($callable)
	{
		assert(func_num_args() >= 1);
		$this->callable = func_get_arg(0);
		assert(is_callable($this->callable));
		if (func_num_args() == 1) {
			$this->args = array();
		} else {
			$this->args = array_slice(func_get_args(), 1);
		}
	}
	
	/**
	 * Returns an instance of the action class that returns the variables passed to it for execution.
	 * 
	 * @return \HotMelt\Action A default action.
	 */
	public function defaultAction()
	{
		$class = __CLASS__;
		return new $class(function($request, $route, $variables) {
			return $variables;
		});
	}
	
	/**
	 * Extracts variables from and processes a request, and generates data.
	 * 
	 * @param \HotMelt\Request $request The request to process.
	 * @param \HotMelt\Route $route A route that matches the request.
	 * @return array The data generated while processing the request.
	 */
	public function perform($request, $route)
	{
		assert(preg_match($route->expr, $request->redirectURL, $matches));
		$variables = array();
		foreach ($matches as $name => $value) {
			$variables[$name] = rawurldecode($value);
		}
		$args = array($request, $route, $variables);
		return call_user_func_array($this->callable, array_merge($args, $this->args));
	}
	
	/**
	 * Makes a second attempt at processing a request after the original attempt has failed.
	 * 
	 * This method is called for the action of the error route registered with `\HotMelt\Route::error()`.
	 * 
	 * @param \HotMelt\Request $request The request to process.
	 * @param \HotMelt\Route $route A route that matches the request.
	 * @param exception $exception The exception that this action should handle.
	 * @return array The data generated while processing the request.
	 */
	public function performForException($request, $route, $exception)
	{
		$args = array($request, $route, $exception);
		return call_user_func_array($this->callable, array_merge($args, $this->args));
	}
}