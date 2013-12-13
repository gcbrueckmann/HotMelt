<?php
/**
 * Defines the `\HotMelt\PDOStatementDecorator` class.
 */
namespace HotMelt;

/**
 * Implements a wrapper around the default `\PDOStatement` class providing features required by the HotMelt ORM layer.
 * 
 * @see PDO
 * @see PersistentObject
 */
class PDOStatementDecorator
{
	/** @ignore */
	private $_pdo;
	/** @ignore */
	private $_statement;
	
	/**
	 * Initialize a statement decorator.
	 * 
	 * @param \PDOStatement $statement The PDO statement to decorate.
	 * @param PDO $pdo The PDO to which the decorated statement belongs.
	 */
	public function __construct($statement, $pdo)
	{
		$this->_statement = $statement;
		$this->_pdo = $pdo;
	}
	
	/** @ignore */
	public function fetchObject()
	{
		$args = func_get_args();
		$obj = call_user_func_array(array($this->_statement, 'fetchObject'), $args);
		if ($obj !== false && is_a($obj, '\HotMelt\PersistentObject')) {
			$obj->pdo = $this->_pdo;
		}
		return $obj;
	}
	
	/** @ignore */
	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->_statement, $name), $arguments);
	}
}