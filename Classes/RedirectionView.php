<?php
namespace HotMelt;

class RedirectionView extends View
{
	public function render($data)
	{
		$this->statusCode = $data['statusCode'];
		$this->headers['Location'] = $data['location'];
		return 'Redirecting to '.$data['location'];
	}
	
	public static function contentType()
	{
		return 'text/plain; charset=UTF-8';
	}
}