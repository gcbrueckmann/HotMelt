<?php
/**
 * Defines the `\HotMelt\Route` class.
 * 
 * When loading this file, route definitions from `route.php` in the site directory are loaded automatically as well.
 */
namespace HotMelt;

/**
 * Maps URI patterns to actions and views.
 * 
 * Routes are declared through the interfaces provided by the `\HotMelt\Route` class.
 * HotMelt will automatically load `Site/routes.php`, so you should use that file to declare routes.
 * 
 * @property-read \HotMelt\Action $action The action to execute for this route.
 * @property-read mixed $view The view to render the result of this route's action in.
 *	Either an instance of `\HotMelt\View` or `null`, if no view as been declared for this route.
 * 
 * @see Action
 * @see View
 */
class Route
{
	/** @ignore */
	private static $routes = array();
	
	/**
	 * Adds a route to the routing table.
	 * 
	 * @param string $expr A regular expression to match against the request URI.
	 * @param mixed $action The action to execute for the route.
	 *   Can be either a subclass of `\HotMelt\Action` or a callable.
	 * @param mixed $view The view that the data returned from the route's action should be rendered in.
	 *   Can be either a subclass of `\HotMelt\View` or the name of a template to be rendered by an instance of `\HotMelt\TemplateView`.
	 *   You may pass `null` if you know that the action will never return (because it will always throw an exception).
	 * @param mixed $methods A string or an array of strings identifying valid HTTP methods for this route.
	 *   You may pass `false` to allow requests with any HTTP method.
	 * @param mixed $options The options for this route.
	 *   Upon adding a route, these options are merged with any default options set with `\HotMelt\Route::pushDefaultOptions()`.
	 * @return void
	 * 
	 * @see Action
	 * @see View
	 */
	public static function add($expr, $action, $view = null, $methods = false, $options = false)
	{
		$class = __CLASS__;
		self::$routes[] = new $class($expr, $action, $view, $methods, $options);
	}
	
	/** @ignore */
	private static $_defaultOptions = array(array());
	
	/**
	 * Pushes (adds) a new set of options onto the default options stack.
	 * 
	 * Any options pushed through this method will override options previously pushed.
	 * 
	 * @param array $options An array of options to push onto the default options stack.
	 * @return void
	 */
	public static function pushDefaultOptions($options)
	{
		self::$_defaultOptions[] = $options;
	}
	
	/**
	 * Pops (removes) a set of options from the top of the default options stack.
	 * 
	 * @return void
	 */
	public static function popDefaultOptions()
	{
		assert(count(self::$_defaultOptions) > 1);
		self::$_defaultOptions = array_slice(self::$_defaultOptions, 0, count(self::$_defaultOptions) - 1);
	}
	
	/**
	 * Returns the effective set of options by merging all sets of options on the default options stack.
	 * 
	 * @param array|bool $options An array of options to merge with the options from the default options stack.
	 *   Passing a set of options in this argument is conceptually similar to pushing that set onto the
	 *   default options stack, then calling this method, and finally popping that set back off the stack,
	 *   except the default options stack is not actually changed.
	 * @return array A set of options.
	 */
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
	
	/**
	 * Initialize a route.
	 * 
	 * @param string $expr A regular expression to match against the request URI.
	 * @param mixed $action The action to execute for this route.
	 *   Can be either a subclass of `\HotMelt\Action` or a callable.
	 * @param mixed $view The view that the data returned from this route's action should be rendered in.
	 *   Can be either a subclass of `\HotMelt\View` or the name of a template to be rendered by an instance of `\HotMelt\TemplateView`.
	 *   You may pass `null` if you know that the action will never return (because it will always throw an exception).
	 * @param mixed $methods A string or an array of strings identifying valid HTTP methods for this route.
	 *   You may pass `false` to allow requests with any HTTP method.
	 * @param mixed $options The options for this route.
	 *   Upon adding a route, these options are merged with any default options set with `\HotMelt\Route::pushDefaultOptions()`.
	 * 
	 * @see Action
	 * @see View
	 */
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
	
	/** @ignore */
	public function __toString()
	{
		if (is_array($this->view)) {
			$view = "['".implode("', '", $this->view)."']";
		} else {
			$view = print_r($this->view, true);
		}
		return "'".$this->expr."' -> ".$this->controller."($view)";
	}
	
	/**
	 * @var string A regular expression to match against the request URI.
	 */
	public $expr;
	/** @ignore */
	private $_viewSpec;
	/** @ignore */
	private $_view;
	
	/** @ignore */
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
	
	/**
	 * Returns the value for an option.
	 * 
	 * @param string $name An option name.
	 * @return mixed
	 */
	public function option($name)
	{
		if (!isset($this->options[$name])) {
			return null;
		}
		return $this->options[$name];
	}
	
	/**
	 * Returns a snapshot of the routing table.
	 * 
	 * @return array
	 */
	public static function routes()
	{
		return self::$routes;
	}
	
	/**
	 * Finds the best match for a request.
	 * 
	 * @param mixed $requestOrURI Either a `\HotMelt\Request` object or a URI (represented as a string).
	 * @return \HotMelt\Route The route that is the best match from the routing table or `false` if there was no match.
	 */
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
	
	/**
	 * Returns `true` if a given HTTP method is valid for this route.
	 * 
	 * @param string $method The HTTP method to test.
	 * @return `true`, if the given method is valid for this route, or `false`, if it is not.
	 * 
	 * @todo Rename to 'acceptsMethod()' for 1.1.0.
	 */
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
	
	/** @ignore */
	private static $errorRoute;
	
	/**
	 * Gets or sets the error route.
	 * 
	 * Call this method without any arguments to retrieve the error route previously set through this method.
	 * Call this method with at least the `$action` and `$view` arguments to set the error route.
	 * 
	 * @param mixed $action The action to execute for the error route.
	 *   Can be either a subclass of `\HotMelt\Action` or a callable.
	 * @param mixed $view The view that the data returned from the error route's action should be rendered in.
	 *   Can be either a subclass of `\HotMelt\View` or the name of a template to be rendered by an instance of `\HotMelt\TemplateView`.
	 *   You may pass `null` if you know that the action will never return (because it will always throw an exception).
	 *   You may pass `false` to allow requests with any HTTP method.
	 * @param mixed $options The options for the error route.
	 *   Upon adding a route, these options are merged with any default options set with `\HotMelt\Route::pushDefaultOptions()`.
	 * @return \HotMelt\Route The error route.
	 */
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
	
	/**
	 * Returns the content types for which this route provides views.
	 * @return array
	 */
	private function contentTypes()
	{
		return array_keys($this->view);
	}
	
	/**
	 * Returns the best content type from a range of acceptable types.
	 * @param mixed $acceptableTypes The types from which to select a match.
	 *   If you pass `false`, the first content type declared for this route is returned.
	 * @return string The best content type for this route.
	 */
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
	
	/**
	 * Returns the best view for a request.
	 * @param Request $request The request for which to find the best view.
	 * @return \HotMelt\View The best view for `$request`.
	 * @throws HTTPErrorException if no matching content type is declared for this route.
	 */
	public function negotiateView($request)
	{
		$contentType = $this->negotiateContentType($request->HTTPAccept);
		if (!isset($this->view[$contentType])) {
			throw new HTTPErrorException(406, "No matching content type for '$request->HTTPAccept'.");
		}
		return View::make($this->view[$contentType], $contentType);
	}
}

require_once(HOTMELT_SITE_DIRECTORY.'/routes.php');