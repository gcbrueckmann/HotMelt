<?php
/**
 * Defines the `\HotMelt\Log` class.
 */
namespace HotMelt;

/**
 * Provides logging capabilities to the HotMelt framework.
 * 
 * @todo Replace/supplement this with a PSR-3-compatible logger interface (http://www.php-fig.org/psr/psr-3/).
 */
class Log
{
	/**
	 * When the logging level is set to this value via `\HotMelt\Log::setLevel()`, no messages will be logged.
	 */
	const LEVEL_NONE    = 0;
	/**
	 * Logging level identifying runtime errors.
	 */
	const LEVEL_ERROR   = 10;
	/**
	 * Logging level identifying exceptional occurrences that are not errors.
	 */
	const LEVEL_WARNING = 20;
	/**
	 * Logging level identifying normal but interesting events.
	 */
	const LEVEL_INFO    = 30;
	
	/** @ignore */
	private static $level = self::LEVEL_ERROR;
	
	/**
	 * Update the effective logging level.
	 * 
	 * @param int $level The logging level to set. Any of the `\HotMelt\Log::LEVEL_...` constants.
	 */
	public static function setLevel($level)
	{
		self::$level = $level;
	}
	
	/**
	 * Return the effective logging level.
	 * 
	 * @return int
	 * 
	 * @todo Rename to 'getLevel()' for 1.1.0.
	 */
	public static function get_level()
	{
		return self::$level;
	}
	
	/**
	 * Write a message to the log.
	 * 
	 * @param int $level The logging level for the message to write. Will not write the message, if the logging level is above the effective logging level.
	 * @param string $message The message to write.
	 */
	private static function write($level, $message)
	{
		if ($level > self::$level) {
			return;
		}
		error_log($message);
	}
	
	/**
	 * Writes a message with a level of `LEVEL_ERROR` to the log.
	 * @param string $message The message to write.
	 */
	public static function error($message)
	{
		self::write(self::LEVEL_ERROR, $message);
	}
	
	/**
	 * Writes a message with a level of `LEVEL_WARNING` to the log.
	 * @param string $message The message to write.
	 */
	public static function warning($message)
	{
		self::write(self::LEVEL_WARNING, $message);
	}
	
	/**
	 * Writes a message with a level of `LEVEL_INFO` to the log.
	 * @param string $message The message to write.
	 */
	public static function info($message)
	{
		self::write(self::LEVEL_INFO, $message);
	}
}