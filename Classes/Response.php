<?php
/**
 * Defines the `\HotMelt\Resposne` class.
 */
namespace HotMelt;

/**
 * Collects information about a response to send for a request.
 */
class Response
{
	/**
	 * The HTTP status code to send.
	 * @type int
	 */
	public $statusCode;
	/**
	 * The HTTP headers to send.
	 * @type array
	 */
	public $headers;
	/**
	 * The body data to send.
	 * @type string
	 */
	public $body;
	
	/**
	 * Initialize a response.
	 * 
	 * @param int $statusCode The HTTP status code to send.
	 * @param array $headers The HTTP headers to send.
	 * @param string $body The body data to send.
	 */
	public function __construct($statusCode, $headers, $body)
	{
		$this->statusCode = $statusCode;
		$this->headers = $headers;
		$this->body = $body;
	}
	
	/**
	 * Send response headers and write body data to output.
	 */
	public function send()
	{
		HTTP::statusHeader($this->statusCode);
		foreach ($this->headers as $name => $value) {
			header($name.': '.$value);
		}
		echo $this->body;
	}
}