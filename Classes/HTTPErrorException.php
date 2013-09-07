<?php
namespace HotMelt;

class HTTPErrorException extends \Exception
{
	public function __construct($statusCode, $message, $headers = null)
	{
		parent::__construct($message);
		$this->statusCode = $statusCode;
		$this->headers = $headers === null ? array() : $headers;
	}
}