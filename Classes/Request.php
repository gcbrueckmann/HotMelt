<?php
namespace HotMelt;

class Request
{
	private static $HTTPServerRequest;
	
	public static function HTTPServerRequest()
	{
		if (!isset(self::$HTTPServerRequest)) {
			$class = __CLASS__;
			self::$HTTPServerRequest = new $class();
			self::$HTTPServerRequest->protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
			self::$HTTPServerRequest->serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
			self::$HTTPServerRequest->requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
			self::$HTTPServerRequest->queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
			self::$HTTPServerRequest->requestURI = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
			self::$HTTPServerRequest->HTTPAccept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
			self::$HTTPServerRequest->redirectURL = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : null;
		}
		return self::$HTTPServerRequest;
	}
	
	public function __toString()
	{
		return $this->redirectURL;
	}
	
	public function __isset($key)
	{
		if ($key == 'rootURL') {
			return true;
		} elseif ($key == 'url') {
			return true;
		}
	}
	
	public function __get($key)
	{
		if ($key == 'rootURL') {
			return $this->protocol.'://'.$this->serverName;
		} elseif ($key == 'url') {
			return $this->rootURL.$this->requestURI;
		}
	}
}