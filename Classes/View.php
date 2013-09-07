<?php
namespace HotMelt;

class View
{
	public function __construct($contentType)
	{
		$this->contentType = $contentType;
		$this->headers = array('Content-Type' => $this->contentType);
		$this->statusCode = 200;
	}
	
	public function __toString()
	{
		return get_class($this);
	}
	
	public function render($data)
	{
		return print_r($data);
	}
	
	public static function make($name, $contentType)
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