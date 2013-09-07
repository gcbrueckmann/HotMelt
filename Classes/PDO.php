<?php
namespace HotMelt;

class PDO extends \PDO
{
	public function __construct()
	{
		call_user_func_array('parent::__construct', func_get_args());
		$this->dsn = func_get_arg(0);
	}

	public static function MySQL($host, $db, $user, $password)
	{
		$class = __CLASS__;
		return new $class("mysql:host=$host;dbname=$db", $user, $password, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	}
	
	public static function Sqlite($path)
	{
		$class = __CLASS__;
		return new $class("sqlite:$path");
	}
	
	public function __toString()
	{
		return $this->dsn;
	}
	
	public function escape($string)
	{
		$dsnScheme = substr($this->dsn, 0, strpos($this->dsn, ':'));
		if ($dsnScheme == 'sqlite') {
			return sqlite_escape_string($string);
		} elseif ($dsnScheme == 'mysql') {
			return mysql_escape_string($string);
		} else {
			throw new \Exception("Unknown DSN scheme ($dsnScheme).");
		}
	}
	
	public function prepare()
	{
		$args = func_get_args();
		$statement = call_user_func_array(array($this, 'parent::prepare'), $args);
		return new PDOStatementDecorator($statement, $this);
	}
	
	public function quoteIdentifier($identifier)
	{
		return '`'.str_replace('`', '``',$identifier).'`';
	}
}