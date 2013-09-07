<?php
namespace HotMelt;

class Log
{
	const LEVEL_NONE    = 0;
	const LEVEL_ERROR   = 10;
	const LEVEL_WARNING = 20;
	const LEVEL_INFO    = 30;
	
	private static $level = self::LEVEL_ERROR;
	
	public static function setLevel($level)
	{
		self::$level = $level;
	}
	
	public static function get_level()
	{
		return self::$level;
	}
	
	private static function write($level, $message)
	{
		if ($level > self::$level) {
			return;
		}
		error_log($message);
	}
	
	public static function error($message)
	{
		self::write(self::LEVEL_ERROR, $message);
	}
	
	public static function warning($message)
	{
		self::write(self::LEVEL_WARNING, $message);
	}
	
	public static function info($message)
	{
		self::write(self::LEVEL_INFO, $message);
	}
}