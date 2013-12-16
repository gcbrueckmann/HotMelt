<?php
/**
 * Defines the deprecated `\HotMelt\RedirectionView` class.
 */
namespace HotMelt;

/**
 * A deprecated way to implement redirections.
 * 
 * @deprecated 1.1.0 Deprecated since HotMelt 1.0.0.
 */
class RedirectionView extends View
{
	/** @ignore */
	public function render($data)
	{
		$this->statusCode = $data['statusCode'];
		$this->headers['Location'] = $data['location'];
		return 'Redirecting to '.$data['location'];
	}
	
	/** @ignore */
	public static function contentType()
	{
		return 'text/plain; charset=UTF-8';
	}
}