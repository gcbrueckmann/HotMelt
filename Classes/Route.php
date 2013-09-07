<?php
namespace HotMelt;

class Route
{
	private static $routes = array();
	
	public static function add($expr, $action, $view = null, $methods = false, $options = false)
	{
		$class = __CLASS__;
		self::$routes[] = new $class($expr, $action, $view, $methods, $options);
	}
	
	private static $_defaultOptions = array(array());
	
	public static function pushDefaultOptions($options)
	{
		self::$_defaultOptions[] = $options;
	}
	
	public static function popDefaultOptions()
	{
		assert(count(self::$_defaultOptions) > 1);
		self::$_defaultOptions = array_slice(self::$_defaultOptions, 0, count(self::$_defaultOptions) - 1);
	}
	
	private static function effectiveOptions($options = false)
	{
		$effectiveOptions = array();
		foreach (self::$_defaultOptions as $defaultOptions) {
			$effectiveOptions = array_merge($effectiveOptions, $defaultOptions);
		}
		if ($options !== false) {
			$effectiveOptions = array_merge($effectiveOptions, $options);
		}
		return $effectiveOptions;
	}
	
	public function __construct($expr, $action, $view, $methods = false, $options = false)
	{
		$this->expr = $expr;
		$this->_actionSpec = $action;
		$this->_viewSpec = $view;
		if ($methods !== false && !is_array($methods)) {
			$this->methods = array($methods);
		} else {
			$this->methods = $methods;
		}
		$this->options = self::effectiveOptions($options);
	}
	
	public function __toString()
	{
		if (is_array($this->view)) {
			$view = "['".implode("', '", $this->view)."']";
		} else {
			$view = print_r($this->view, true);
		}
		return "'".$this->expr."' -> ".$this->controller."($view)";
	}
	
	private $_viewSpec;
	private $_view;
	
	public function __get($key)
	{
		if ($key == 'view') {
			if (!isset($this->_view)) {
				if (is_array($this->_viewSpec)) {
					$this->_view = $this->_viewSpec;
				} else {
					if (preg_match("/^([_a-z][_a-z0-9]*\\\\)*[_a-z][_a-z0-9]*$/i", $this->_viewSpec) && is_subclass_of($this->_viewSpec, 'HotMelt\\View')) {
						$contentType = call_user_func(array($this->_viewSpec, 'contentType'));
						if (($relevantLength = strpos($contentType, ';')) !== false) {
							$contentType = substr($relevantLength, 0, $relevantLength);
						}
						$this->_view = array($contentType => $this->_viewSpec);
					} else {
						$extension = pathinfo($this->_viewSpec, PATHINFO_EXTENSION);
						if (preg_match('/html?/i', $extension)) {
							$this->_view = array('text/html' => $this->_viewSpec);
						} elseif (preg_match('/xhtml?/i', $extension)) {
							$this->_view = array('application/xhtml+xml' => $this->_viewSpec);
						} else {
							Log::error('Could not infer content type for view '.$this->_viewSpec.'.');
							return false;
						}
					}
				}
			}
			return $this->_view;
		} elseif ($key == 'action') {
			if (!isset($this->_action)) {
				if ($this->_actionSpec === null) {
					$this->_action = Action::defaultAction();
				} elseif (strpos($this->_actionSpec, '::') !== false) {
					$this->_action = new Action($this->_actionSpec);
				} elseif (is_a($this->_actionSpec, 'HotMelt\\Action')) {
					$this->_action = $this->_actionSpec;
				} else {
					$this->_action = new Action($this->_actionSpec);
				}
			}
			return $this->_action;
		}
	}
	
	public function option($name)
	{
		if (!isset($this->options[$name])) {
			return null;
		}
		return $this->options[$name];
	}
	
	public static function routes()
	{
		return self::$routes;
	}
	
	public static function find($requestOrURI)
	{
		$redirectURL = is_a($requestOrURI, '\\HotMelt\\Request') ? $requestOrURI->redirectURL : $requestOrURI;
		foreach (self::$routes as $route) {
			if (preg_match($route->expr, $redirectURL) == 1) {
				return $route;
			}
		}
		return false;
	}
	
	public function accepts_method($method)
	{
		if ($this->methods === false) {
			return true;
		}
		foreach ($this->methods as $validMethod) {
			if (strcasecmp($method, $validMethod) === 0) {
				return true;
			}
		}
		return false;
	}
	
	private static $errorRoute;
	
	public static function error($action = null, $view = null, $options = null)
	{
		if (func_num_args() == 0) {
			return self::$errorRoute;
		} else {
			assert(!($action === null && $view === null));
			$class = __CLASS__;
			self::$errorRoute = new $class(null, $action, $view, false, $options);
			return self::$errorRoute;
		}
	}
	
	private function contentTypes()
	{
		return array_keys($this->view);
	}
	
	private function negotiateContentType($acceptableTypes)
	{
		spl_autoload_register(function ($class) {
			@include_once(dirname(__FILE__).'/../negotiation/src/'.str_replace('\\', '/', $class).'.php');
		});
		if (!$acceptableTypes) {
			$contentTypes = $this->contentTypes();
			return $contentTypes[0];
		}
		$negotiator = new \Negotiation\FormatNegotiator();
		return $negotiator->getBest($acceptableTypes, $this->contentTypes())->getValue();
	}
	
	public function negotiateView($request)
	{
		$contentType = $this->negotiateContentType($request->HTTPAccept);
		if (!isset($this->view[$contentType])) {
			throw new HTTPErrorException(406, "No matching content type for '$request->HTTPAccept'.");
		}
		return View::make($this->view[$contentType], $contentType);
	}
}

require_once(dirname(__FILE__).'/../../Site/routes.php');