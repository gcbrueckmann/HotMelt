<?php
namespace HotMelt;

class PersistentObject
{
	public function __construct($fetchedProperties = false)
	{
		if ($fetchedProperties === false) {
			$this->_fetchedProperties = array_keys($this->publicProperties());
		} else {
			$class = get_class($this);
			assert(!array_key_exists(call_user_func("$class::primaryKey"), $fetchedProperties));
			$this->_fetchedProperties = array_keys($fetchedProperties);
			foreach ($this->_fetchedProperties as $name) {
				$this->{$name} = $fetchedProperties[$name];
			}
		}
	}

	public static function tableName()
	{
		$class = get_called_class();
		$namespacePrefixLength = strrpos($class, '\\');
		if ($namespacePrefixLength !== false) {
			$class = substr($class, $namespacePrefixLength + 1);
		}
		$inflector = \ICanBoogie\Inflector::get();
		return $inflector->pluralize($class);
	}
	
	public static function primaryKey()
	{
		return 'id';
	}
	
	public static function defaultPDO()
	{
		return null;
	}
	
	private function publicProperties()
	{ 
		$me = $this; 
		$publics = function() use ($me) { 
			return get_object_vars($me); 
		}; 
		return $publics(); 
	}
	
	public function save()
	{
		$quotedTableName = $this->PDO->quoteIdentifier(call_user_func(get_class($this).'::tableName'));
		$boundColumns = array();
		$bindParameters = array();
		$primaryKey = call_user_func(get_class($this).'::primaryKey');
		$isNew = !isset($this->{$primaryKey});
		foreach ($this->_fetchedProperties as $name) {
			if ($isNew && $name == $primaryKey) {
				continue;
			}
			$bindParameters[":$name"] = $this->{$name};
			$boundColumns[] = $name;
		}
		if ($isNew) {
			$assignmentColumns = implode(', ', array_map(function ($column) {
				return $this->PDO->quoteIdentifier($column);
			}, $boundColumns));
			$assignmentValues = implode(', ', array_map(function ($column) {
				return ":$column";
			}, $boundColumns));
			$statement = $this->PDO->prepare("INSERT INTO $quotedTableName ( $assignmentColumns ) VALUES ( $assignmentValues )");
		} else {
			$assignments = implode(', ', array_map(function ($column) {
				return $this->PDO->quoteIdentifier($column)." = :$column";
			}, $boundColumns));
			$statement = $this->PDO->prepare("UPDATE $quotedTableName SET $assignments WHERE ".$this->PDO->quoteIdentifier($primaryKey)." = :$primaryKey");
		}
		if (!$statement->execute($bindParameters)) {
			Log::error("Could not save ".get_class($this)." ".$this->{$primaryKey}.": ".var_export($statement->errorInfo(), true));
			return false;
		}
		if ($isNew) {
			$this->{$primaryKey} = $this->PDO->lastInsertId();
			$this->_fetchedProperties[] = $primaryKey;
		}
		return true;
	}
	
	const FIND_BY_KEY_PREFIX = 'findBy';
	const COUNT_BY_KEY_PREFIX = 'countBy';
	
	public static function __callStatic($name, $arguments)
	{
		if (strpos($name, self::FIND_BY_KEY_PREFIX) === 0) {
			$key = substr($name, strlen(self::FIND_BY_KEY_PREFIX));
			$inflector = \ICanBoogie\Inflector::get();
			$key = $inflector->camelize($key, true);
			return call_user_func_array(__CLASS__.'::_findByKey', array_merge(array(get_called_class(), $key), $arguments));
		} elseif (strpos($name, self::COUNT_BY_KEY_PREFIX) === 0) {
			$key = substr($name, strlen(self::COUNT_BY_KEY_PREFIX));
			$inflector = \ICanBoogie\Inflector::get();
			$key = $inflector->camelize($key, true);
			return call_user_func_array(__CLASS__.'::_countByKey', array_merge(array(get_called_class(), $key), $arguments));
		}
	}
	
	private static function _findByKey($class, $keyName, $keyValue, $limit = false, $sorting = false, $PDO = null)
	{
		if ($PDO === null) {
			assert(($PDO = call_user_func("$class::defaultPDO")) !== null);
		}
		$tableName = call_user_func("$class::tableName");
		if ($limit === false) {
			$limit = '';
		} elseif (is_array($limit)) {
			assert(count($limit) == 2);
			$limit = 'LIMIT '.$limit[0].','.$limit[1];
		} else {
			$limit = "LIMIT $limit";
		}
		if ($sorting === false) {
			$sorting = '';
		} elseif (strpos($sorting, '+') === 0) {
			$sorting = 'ORDER BY '.$PDO->quoteIdentifier(substr($sorting, 1)).' ASC';
		} elseif (strpos($sorting, '-') === 0) {
			$sorting = 'ORDER BY '.$PDO->quoteIdentifier(substr($sorting, 1)).' DESC';
		} else {
			$sorting = 'ORDER BY '.$PDO->quoteIdentifier($sorting).' ASC';
		}
		Log::info("SELECT * FROM ".$PDO->quoteIdentifier($tableName)." WHERE ".$PDO->quoteIdentifier($keyName)." = :$keyName $sorting $limit");
		$statement = $PDO->prepare("SELECT * FROM ".$PDO->quoteIdentifier($tableName)." WHERE ".$PDO->quoteIdentifier($keyName)." = :$keyName $sorting $limit");
		if (!$statement->execute(array(":$keyName" => $keyValue))) {
			return false;
		}
		$results = array();
		while (($obj = $statement->fetchObject($class)) !== false) {
			$results[] = $obj;
		}
		return $results;
	}
	
	private static function _countByKey($class, $keyName, $keyValue, $limit = false, $PDO = null)
	{
		if ($PDO === null) {
			assert(($PDO = call_user_func("$class::defaultPDO")) !== null);
		}
		$tableName = call_user_func("$class::tableName");
		if ($limit === false) {
			$limit = '';
		} else {
			$limit = "LIMIT $limit";
		}
		$statement = $PDO->prepare("SELECT COUNT(*) AS rowCount FROM ".$PDO->quoteIdentifier($tableName)." WHERE ".$PDO->quoteIdentifier($keyName)." = :$keyName $limit");
		if (!$statement->execute(array(":$keyName" => $keyValue))) {
			return false;
		}
		if (($result = $statement->fetchObject()) === false) {
			return false;
		}
		return intval($result->rowCount);
	}
	
	public static function insert($properties = false, $PDO = null)
	{
		if ($PDO === null) {
			$class = get_called_class();
			assert(($PDO = call_user_func("$class::defaultPDO")) !== null);
		}
		$reflector = new \ReflectionClass(get_called_class());
		$obj = $reflector->newInstance($properties);
		$obj->PDO = $PDO;
		if (!$obj->save()) {
			return false;
		}
		return $obj;
	}
	
	public function delete()
	{
		$class = get_class($this);
		$tableName = call_user_func("$class::tableName");
		$primaryKey = call_user_func("$class::primaryKey");
		Log::info("DELETE FROM ".$this->PDO->quoteIdentifier($tableName)." WHERE ".$this->PDO->quoteIdentifier($primaryKey)." = :$primaryKey LIMIT 1");
		$statement = $this->PDO->prepare("DELETE FROM ".$this->PDO->quoteIdentifier($tableName)." WHERE ".$this->PDO->quoteIdentifier($primaryKey)." = :$primaryKey LIMIT 1");
		return $statement->execute(array(":$primaryKey" => $this->{$primaryKey}));
	}
	
	public function getCachedProperty($key, $getter)
	{
		if (!isset($this->{"_$key"})) {
			$this->{"_$key"} = call_user_func($getter);
		}
		return $this->{"_$key"};
	}
	
	public function purgeCachedProperty($key)
	{
		unset($this->{"_$key"});
	}
}