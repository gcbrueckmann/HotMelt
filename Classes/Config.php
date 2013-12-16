<?php
/**
 * Defines the `\HotMelt\Config` class.
 * 
 * This file will try to load these configuration files:
 * 
 * - `Site/config.php`
 * - `Site/config-<DOMAIN>.php`
 * 
 * For a host name beginning with *www.*, this file will also try to load the configuration file for the corresponding host name without *www.*.
 */
namespace HotMelt;

/**
 * Provides an interface for managing configuration options.
 * 
 * To get an set options, either use `\HotMelt\Config::get()` and \HotMelt\Config::set()`, respectively,
 * or take advantage of the fact that `\HotMelt\Config` allows arbitrary method overloading
 * 
 * `\HotMelt\Config` parses these configuration files:
 * 
 * - `Site/config.php`
 * - `Site/config-<DOMAIN>.php`
 * 
 * For a host name beginning with *www.*, `\HotMelt\Config` will also try to load the configuration file for the corresponding host name without *www.*.
 */
final class Config
{
	/** @ignore */
	private static $options = array(
		'logLevel' => Log::LEVEL_WARNING
	);
	
	/**
	 * Update the value for a configuration option.
	 * 
	 * @param string $name The name of the option to update.
	 * @param mixed $value The value to set for `$name`.
	 * @return void
	 */
	public static function set($name, $value)
	{
		self::$options[$name] = $value;
	}
	
	/**
	 * Returns the value for a configuration option.
	 * 
	 * @param string $name The name of the option whose value you are interested in.
	 * @return mixed
	 */
	public static function get($name)
	{
		if (!isset(self::$options[$name])) {
			return null;
		}
		return self::$options[$name];
	}
	
	/**
	 * Returns a snapshot of the current configuration.
	 * 
	 * @return array
	 */
	public static function allOptions()
	{
		return array_keys(self::$options);
	}
	
	/** @ignore */
	private static function getOrSet($name, $args)
	{
		if (count($args)) {
			self::set($name, $args[0]);
		}
		return self::get($name);
	}
	
	/** @ignore */
	public static function __callStatic($name, $args)
	{
		return self::getOrSet($name, $args);
	}
}

if (!(isset($_SERVER) && array_key_exists('SERVER_NAME', $_SERVER))) {
	Config::host('localhost');
	Config::server(null);
} else {
	Config::host($_SERVER['SERVER_NAME']);
	if (empty($_SERVER['HTTPS'])) {
		Config::server('http://'.$_SERVER['SERVER_NAME'].'/');
	} else {
		Config::server('https://'.$_SERVER['SERVER_NAME'].'/');
	}
}

$siteConfigDir = dirname(__FILE__).'/../../Site';
require_once("$siteConfigDir/config.php");
$host = Config::host();
$configCandidates = array("$siteConfigDir/config-$host.php");
if (strpos($host, 'www.') === 0) {
	$hostWithoutWWW = substr($host, 4);
	$configCandidates[] = "$siteConfigDir/config-$hostWithoutWWW.php";
}
foreach ($configCandidates as $config) {
	if (file_exists($config)) {
		require_once($config);
		break;
	}
}