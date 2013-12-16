<?php
/**
 * Defines the `\HotMelt\Request` class.
 */
namespace HotMelt;

/**
 * Collects information about requests handled by the HotMelt dispatch machinery.
 * 
 * @property-read string $rootURL The root URL (comprised of only the protocol and host name) of the request.
 * @property-read string $url The full URL of the 
 */
class Request
{
	/** @ignore */
	private static $httpServerRequest;
	
	/**
	 * The protocol for the request, i.e. `'http'` or `'https'`.
	 * @type string
	 */
	public $protocol;
	/**
	 * The name of the server host under which the current script is executing. If the script is running on a virtual host, this will be the value defined for that virtual host.
	 * @type string
	 */
	public $serverName;
	/**
	 * Which request method was used to access the page; i.e. `'GET'`, `'HEAD'`, `'POST'`, `'PUT'`.
	 * @type string
	 */
	public $requestMethod;
	/**
	 * The query string, if any, via which the page was accessed.
	 * @type string
	 */
	public $queryString;
	/**
	 * The URI which was given in order to access this page; for instance, `'/index.html'`.
	 * @type string
	 */
	public $requestURI;
	/**
	 * Contents of the `Accept:` header from the current request, if there is one.
	 * @type string
	 * @todo Rename to 'httpAccept' for 1.1.0.
	 */
	public $HTTPAccept;
	/**
	 * Redirect URL. Set only by some servers, e.g. Apache.
	 * @type string
	 */
	public $redirectURL;
	
	/**
	 * Return request information based on the `$_SERVER` global.
	 * 
	 * @return Request
	 * 
	 * @todo Rename to 'httpServerRequest' for 1.1.0.
	 */
	final public static function HTTPServerRequest()
	{
		if (!isset(self::$httpServerRequest)) {
			$class = __CLASS__;
			self::$httpServerRequest = new $class();
			self::$httpServerRequest->protocol = empty($_SERVER['HTTPS']) ? 'http' : 'https';
			self::$httpServerRequest->serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : null;
			self::$httpServerRequest->requestMethod = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : null;
			self::$httpServerRequest->queryString = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;
			self::$httpServerRequest->requestURI = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : null;
			self::$httpServerRequest->HTTPAccept = isset($_SERVER['HTTP_ACCEPT']) ? $_SERVER['HTTP_ACCEPT'] : null;
			self::$httpServerRequest->redirectURL = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : null;
		}
		return self::$httpServerRequest;
	}
	
	/** @ignore */
	public function __toString()
	{
		return $this->redirectURL;
	}
	
	/** @ignore */
	public function __isset($key)
	{
		if ($key == 'rootURL') {
			return true;
		} elseif ($key == 'url') {
			return true;
		}
	}
	
	/** @ignore */
	public function __get($key)
	{
		if ($key == 'rootURL') {
			return $this->protocol.'://'.$this->serverName;
		} elseif ($key == 'url') {
			return $this->rootURL.$this->requestURI;
		}
	}
}