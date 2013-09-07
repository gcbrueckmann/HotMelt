<?php
namespace HotMelt;

class Action
{
	public function __construct(/* $callable, $arg1, ... */)
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
	
	public function defaultAction()
	{
		$class = __CLASS__;
		return new $class(function($request, $route, $variables) {
			return $variables;
		});
	}
	
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
	
	public function performForException($request, $route, $exception)
	{
		$args = array($request, $route, $exception);
		return call_user_func_array($this->callable, array_merge($args, $this->args));
	}
}