<?php
namespace HotMelt;

class Config
{
	private static $options = array(
		'logLevel' => Log::LEVEL_WARNING
	);
	
	public static function set($name, $value)
	{
		self::$options[$name] = $value;
	}
	
	public static function get($name)
	{
		if (!isset(self::$options[$name])) {
			return null;
		}
		return self::$options[$name];
	}
	
	public static function allOptions()
	{
		return array_keys(self::$options);
	}
	
	private static function getOrSet($name, $args)
	{
		if (count($args)) {
			self::set($name, $args[0]);
		}
		return self::get($name);
	}
	
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

$siteConfigDir = dirname(__FILE__).'/../../site';
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