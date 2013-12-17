<?php
/**
 * Defines the `\HotMelt\View` class.
 */
namespace HotMelt;

/**
 * Coordinates rendering of the data produced by an action.
 * 
 * @see Action
 * @see Route
 */
class View
{
	/**
	 * HTTP response headers for the view. Prepopulated with a `Content-Type` header as specified when initializing the view.
	 * @type array
	 */
	public $headers;
	/**
	 * HTTP status code for the view. Defaults to `200`.
	 * @type int
	 */
	public $statusCode;
	
	/**
	 * Initialize a view object.
	 * 
	 * @param string $contentType The content type to assign to the view.
	 */
	public function __construct($contentType)
	{
		$this->headers = array('Content-Type' => $contentType);
		$this->statusCode = 200;
	}
	
	/** @ignore */
	public function __toString()
	{
		return get_class($this);
	}
	
	/**
	 * Render the data produced by an action.
	 * 
	 * @param array $data The data produced by an action.
	 * @return string
	 */
	public function render($data)
	{
		return print_r($data);
	}
	
	/**
	 * Create a view instance based on a view specification.
	 * 
	 * @param string $name Class or template name of the view to create. If no class by this name exists, an instance of `\HotMelt\TemplateView` will be returned.
	 * @param string $contentType The content type to assign to the view.
	 * @return \HotMelt\View
	 */
	final public static function make($name, $contentType)
	{
		try {
			$reflector = new \ReflectionClass($name);
			return $reflector->newInstance($contentType);
		} catch (\ReflectionException $exception) {
			$view = new TemplateView($contentType, $name);
			return $view;
		}
	}
}