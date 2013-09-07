<?php
namespace HotMelt;

class PDOStatementDecorator
{
	public function __construct($statement, $PDO)
	{
		$this->_statement = $statement;
		$this->_PDO = $PDO;
	}
	
	public function fetchObject()
	{
		$args = func_get_args();
		$obj = call_user_func_array(array($this->_statement, 'fetchObject'), $args);
		if ($obj !== false && is_a($obj, '\HotMelt\PersistentObject')) {
			$obj->PDO = $this->_PDO;
		}
		return $obj;
	}
	
	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->_statement, $name), $arguments);
	}
}