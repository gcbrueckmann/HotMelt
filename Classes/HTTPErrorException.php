<?php
/**
 * Defines the `\HotMelt\HTTPErrorException` class.
 */
namespace HotMelt;

/**
 * Throw an instance of `\HotMelt\HTTPErrorException` to break out of the regular controlf flow.
 */
class HTTPErrorException extends \Exception
{
	/**
	 * The HTTP status code associated with this exception.
	 * 
	 * An exception handler catching an instance of `\HotMelt\HTTPErrorException` should ensure that these headers are sent along with the response.
	 * 
	 * @type int
	 */
	public $statusCode;
	
	/**
	 * Any additional HTTP headers associated with this exception.
	 * 
	 * An exception handler catching an instance of `\HotMelt\HTTPErrorException` should ensure that these headers are sent along with the response.
	 * 
	 * @type array
 	 */
	public $headers;
	
	/**
	 * Intiailize an HTTP error exception object.
	 * 
	 * @param int $statusCode An HTTP status code for this exception.
	 * @param string $message An exception message.
	 * @param array $headers Additional HTTP headers for this exception (optional).
	 */
	public function __construct($statusCode, $message, $headers = null)
	{
		parent::__construct($message);
		$this->statusCode = $statusCode;
		$this->headers = $headers === null ? array() : $headers;
	}
}