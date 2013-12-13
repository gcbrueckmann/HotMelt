<?php
/**
 * Defines the `\HotMelt\JSONView` class.
 */
namespace HotMelt;

/**
 * Renders a JSON-encoded representation of the data produced by a route's action.
 */
class JSONView extends View
{
	/** @ignore */
	public static function contentType()
	{
		return 'application/json';
	}
	
	/** @ignore */
	public function render($data)
	{
		return json_encode($data);
	}
}