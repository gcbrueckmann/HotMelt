<?php
namespace HotMelt;

class JSONView extends View
{
	public static function contentType()
	{
		return 'application/json';
	}

	public function render($data)
	{
		return json_encode($data);
	}
}