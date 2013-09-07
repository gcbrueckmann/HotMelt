<?php
namespace HotMelt;

class Response
{
	public function __construct($statusCode, $headers, $body)
	{
		$this->statusCode = $statusCode;
		$this->headers = $headers;
		$this->body = $body;
	}
	
	public function send()
	{
		HTTP::statusHeader($this->statusCode);
		foreach ($this->headers as $name => $value) {
			header($name.': '.$value);
		}
		echo $this->body;
	}
}