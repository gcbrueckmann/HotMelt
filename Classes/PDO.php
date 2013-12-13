<?php
/**
 * Defines the `\HotMelt\PDO` class.
 */
namespace HotMelt;

/**
 * Extends to default `PDO` class with capabilities used by other HotMelt classes.
 * 
 * Extensions include a wrapper around `\PDO::prepare()` that wraps statements in an instance of `\HotMelt\PDOStatementDecorator` and support for quoting identifiers (`\HotMelt\PDO::quoteIdentifier()`).
 */
class PDO extends \PDO
{
	/**
	 * The DSN used to initialize the PDO object.
	 * @type string
	 */
	public $dsn;
	
	/** @ignore */
	public function __construct()
	{
		call_user_func_array('parent::__construct', func_get_args());
		$this->dsn = func_get_arg(0);
	}
	
	/**
	 * Conveniently create a PDO object using the MySQL driver.
	 * 
	 * This method will also configure the connection to use the UTF-8 character encoding.
	 * 
	 * @param string $host The host to connect to.
	 * @param string $db The name of the database to use.
	 * @param string $user The user name to use for authentication.
	 * @param string $password The password to use for authentication.
	 * @return \HotMelt\PDO
	 * 
	 * @todo Rename to 'mySQL()' for 1.1.0.
	 */
	public static function MySQL($host, $db, $user, $password)
	{
		$class = __CLASS__;
		return new $class("mysql:host=$host;dbname=$db", $user, $password, array(\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"));
	}
	
	/**
	 * Conveniently create a PDO object using the MySQL driver.
	 * 
	 * This method will also configure the connection to use the UTF-8 character encoding.
	 * 
	 * @param string $path The path to the Sqlite database file.
	 * @return \HotMelt\PDO
	 * 
	 * @todo Rename to 'sqlite()' for 1.1.0.
	 */
	public static function Sqlite($path)
	{
		$class = __CLASS__;
		return new $class("sqlite:$path");
	}
	
	/** @ignore */
	public function __toString()
	{
		return $this->dsn;
	}
	
	/**
	 * Escape (quote) a string for direct injection into a query string.
	 * 
	 * This method is semi-driver-aware and will use either `sqlite_escape_string()` or `mysql_escape_string()`.
	 * Note that this method is deprecated, and you should use the `\PDO::quote()` method provided by the default `PDO` class (which `\HotMelt\PDO` inherits).
	 * 
	 * @deprecated 1.1.0 This method is deprecated and will be removed with version 1.1.0. Use the `\PDO::quote()` method provided by the default `PDO` class (which `\HotMelt\PDO` inherits).
	 * @todo Remove for 1.1.0.
	 * 
	 * @param string $string The string to escape (quote).
	 * @return string
	 */
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
	
	/** @ignore */
	public function prepare()
	{
		$args = func_get_args();
		$statement = call_user_func_array(array($this, 'parent::prepare'), $args);
		return new PDOStatementDecorator($statement, $this);
	}
	
	/**
	 * Quotes a string representing an identifier, e.g. a table or column name, for direct injection into a query string.
	 * 
	 * @param string $identifier The identifier to quote.
	 * @return string
	 */
	public function quoteIdentifier($identifier)
	{
		return '`'.str_replace('`', '``',$identifier).'`';
	}
}