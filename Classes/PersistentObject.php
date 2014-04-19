<?php
/**
 * Defines the `\HotMelt\PersistentObject` class.
 */
namespace HotMelt;

/**
 * Implements a lightweight object-relational-mapper interface.
 * 
 * The `PersistentObject` class prefers convention over configuration.
 * This is an overview of the conventions that the `PersistentObject` class establishes and how to override them:
 * 
 * - Objects are read from and written to a table that is the plural form of the subclass name (without the namespace prefix). Override the `tableName()` method to change this.
 * - Objects have a primary key (assumged to be an auto-incrementing integer) named `id`. Override the `primaryKey()` method to change this.
 * - Some methods allow you to specify a `\HotMelt\PDO` object for database access. If not PDO is given, the PDO returned from the `defaultPDO()` class method is used. The default value returned by this method is `null`, however, so you will have to override this method to use a default PDO.
 * 
 * Note that you should not directly create a new persistent object instance. Use the `objectsWith...()` and `insert()` methods to retrieve existing objects or create new ones.
 */
class PersistentObject
{
	/** @ignore */
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
	
	/**
	 * Return the name of the table that stores records for objects of this class.
	 * 
	 * Defaults to the plural form of the class name (without a namespace prefix).
	 * 
	 * @return string
	 */
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
	
	/**
	 * Return the name of the primary key property of objects of this class.
	 * 
	 * Defaults to `'id'`.
	 * 
	 * @return string
	 */
	public static function primaryKey()
	{
		return 'id';
	}
	
	/**
	 * Return the default PDO to use, i.e. for methods that let you specify a PDO to use but are called without specifying a PDO.
	 * 
	 * Defaults to `null`.
	 * 
	 * @return PDO
	 */
	public static function defaultPDO()
	{
		return null;
	}
	
	/** @ignore */
	private function publicProperties()
	{ 
		$me = $this; 
		$publics = function() use ($me) { 
			return get_object_vars($me); 
		}; 
		return $publics(); 
	}
	
	/**
	 * Write changes to an object to the database.
	 * 
	 * For newly inserted objects, this will also set the primary key property.
	 * 
	 * @return boolean `true` if the changes can be saved, `false` if not.
	 */
	public function save()
	{
		$quotedTableName = $this->pdo->quoteIdentifier(call_user_func(get_class($this).'::tableName'));
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
				return $this->pdo->quoteIdentifier($column);
			}, $boundColumns));
			$assignmentValues = implode(', ', array_map(function ($column) {
				return ":$column";
			}, $boundColumns));
			$statement = $this->pdo->prepare("INSERT INTO $quotedTableName ( $assignmentColumns ) VALUES ( $assignmentValues )");
		} else {
			$assignments = implode(', ', array_map(function ($column) {
				return $this->pdo->quoteIdentifier($column)." = :$column";
			}, $boundColumns));
			$statement = $this->pdo->prepare("UPDATE $quotedTableName SET $assignments WHERE ".$this->pdo->quoteIdentifier($primaryKey)." = :$primaryKey");
		}
		if (!$statement->execute($bindParameters)) {
			Log::error("Could not save ".get_class($this)." ".$this->{$primaryKey}.": ".var_export($statement->errorInfo(), true));
			return false;
		}
		if ($isNew) {
			$this->{$primaryKey} = $this->pdo->lastInsertId();
			$this->_fetchedProperties[] = $primaryKey;
		}
		return true;
	}
	
	/** @ignore */
	const QUERY_PREFIX = 'objectsWith';
	/** @ignore */
	const FIND_BY_KEY_PREFIX = 'findBy';
	/** @ignore */
	const COUNT_BY_KEY_PREFIX = 'countBy';
	
	/** @ignore */
	public static function __callStatic($name, $arguments)
	{
		if (strpos($name, self::QUERY_PREFIX) === 0) {
			$query = substr($name, strlen(self::QUERY_PREFIX));
			$inflector = \ICanBoogie\Inflector::get();
			$query = $inflector->camelize($query, true);
			return new PersistentObjectQuery(get_called_class(), $query, $arguments);
		} elseif (strpos($name, self::FIND_BY_KEY_PREFIX) === 0) {
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
	
	/** @ignore */
	private static function _querySet($class, $keyName, $keyValue, $limit = false, $sorting = false, $pdo = null)
	{
		if ($pdo === null) {
			assert(($pdo = call_user_func("$class::defaultPDO")) !== null);
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
			$sorting = 'ORDER BY '.$pdo->quoteIdentifier(substr($sorting, 1)).' ASC';
		} elseif (strpos($sorting, '-') === 0) {
			$sorting = 'ORDER BY '.$pdo->quoteIdentifier(substr($sorting, 1)).' DESC';
		} else {
			$sorting = 'ORDER BY '.$pdo->quoteIdentifier($sorting).' ASC';
		}
		Log::info("SELECT * FROM ".$pdo->quoteIdentifier($tableName)." WHERE ".$pdo->quoteIdentifier($keyName)." = :$keyName $sorting $limit");
		$statement = $pdo->prepare("SELECT * FROM ".$pdo->quoteIdentifier($tableName)." WHERE ".$pdo->quoteIdentifier($keyName)." = :$keyName $sorting $limit");
		if (!$statement->execute(array(":$keyName" => $keyValue))) {
			return false;
		}
		$results = array();
		while (($obj = $statement->fetchObject($class)) !== false) {
			$results[] = $obj;
		}
		return $results;
	}
	
	/** @ignore */
	private static function _findByKey($class, $keyName, $keyValue, $limit = false, $sorting = false, $pdo = null)
	{
		if ($pdo === null) {
			assert(($pdo = call_user_func("$class::defaultPDO")) !== null);
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
			$sorting = 'ORDER BY '.$pdo->quoteIdentifier(substr($sorting, 1)).' ASC';
		} elseif (strpos($sorting, '-') === 0) {
			$sorting = 'ORDER BY '.$pdo->quoteIdentifier(substr($sorting, 1)).' DESC';
		} else {
			$sorting = 'ORDER BY '.$pdo->quoteIdentifier($sorting).' ASC';
		}
		Log::info("SELECT * FROM ".$pdo->quoteIdentifier($tableName)." WHERE ".$pdo->quoteIdentifier($keyName)." = :$keyName $sorting $limit");
		$statement = $pdo->prepare("SELECT * FROM ".$pdo->quoteIdentifier($tableName)." WHERE ".$pdo->quoteIdentifier($keyName)." = :$keyName $sorting $limit");
		if (!$statement->execute(array(":$keyName" => $keyValue))) {
			return false;
		}
		$results = array();
		while (($obj = $statement->fetchObject($class)) !== false) {
			$results[] = $obj;
		}
		return $results;
	}
	
	/** @ignore */
	private static function _countByKey($class, $keyName, $keyValue, $limit = false, $pdo = null)
	{
		if ($pdo === null) {
			assert(($pdo = call_user_func("$class::defaultPDO")) !== null);
		}
		$tableName = call_user_func("$class::tableName");
		if ($limit === false) {
			$limit = '';
		} else {
			$limit = "LIMIT $limit";
		}
		$statement = $pdo->prepare("SELECT COUNT(*) AS rowCount FROM ".$pdo->quoteIdentifier($tableName)." WHERE ".$pdo->quoteIdentifier($keyName)." = :$keyName $limit");
		if (!$statement->execute(array(":$keyName" => $keyValue))) {
			return false;
		}
		if (($result = $statement->fetchObject()) === false) {
			return false;
		}
		return intval($result->rowCount);
	}
	
	/**
	 * Create a new object and saves it to the database.
	 * 
	 * @param array $properties The values to assign for the new object's properties. Set to `false` (rather than an empty array) if you do not wish to assign default values.
	 * @param PDO $pdo The PDO to associate with the new object.
	 * 
	 * @return mixed The newly inserted object if changes could be saved, `false` if not.
	 */
	public static function insert($properties = false, $pdo = null)
	{
		if ($pdo === null) {
			$class = get_called_class();
			assert(($pdo = call_user_func("$class::defaultPDO")) !== null);
		}
		$reflector = new \ReflectionClass(get_called_class());
		$obj = $reflector->newInstance($properties);
		$obj->pdo = $pdo;
		if (!$obj->save()) {
			return false;
		}
		return $obj;
	}
	
	/**
	 * Delete an object.
	 */
	public function delete()
	{
		$class = get_class($this);
		$tableName = call_user_func("$class::tableName");
		$primaryKey = call_user_func("$class::primaryKey");
		Log::info("DELETE FROM ".$this->pdo->quoteIdentifier($tableName)." WHERE ".$this->pdo->quoteIdentifier($primaryKey)." = :$primaryKey LIMIT 1");
		$statement = $this->pdo->prepare("DELETE FROM ".$this->pdo->quoteIdentifier($tableName)." WHERE ".$this->pdo->quoteIdentifier($primaryKey)." = :$primaryKey LIMIT 1");
		return $statement->execute(array(":$primaryKey" => $this->{$primaryKey}));
	}
	
	/**
	 * Retrieve cached property values.
	 * 
	 * Use this method to cache property values that are expensive to compute.
	 * You provide the property name along with a getter.
	 * When you first call this method, `getCachedProperty()` will in turn call your getter to compute the value, store the value returned from the getter in the cache and then return the value to you.
	 * Subsequent calls to this method will return the value stored in the cache and will not result in the getter being called.
	 * 
	 * To invalidate a cached property value use the `\HotMelt\PersistentObject::purgeCachedProperty()` method.
	 * 
	 * @param string $key The name of the property whose value you wish to retrieve.
	 * @param callable $getter A callback function that computes the property value and returns it. This function should not take any parameters.
	 * @return mixed
	 * 
	 * @see \HotMelt\PersistentObject::purgeCachedProperty()
	 */
	public function getCachedProperty($key, $getter)
	{
		if (!isset($this->{"_$key"})) {
			$this->{"_$key"} = call_user_func($getter);
		}
		return $this->{"_$key"};
	}
	
	/**
	 * Invalidates the value for a property cached through `\HotMelt\PersistentObject::getCachedProperty()`.
	 * 
	 * @param string $key The name of the property whose value you wish to purge from the cache.
	 * 
	 * @see \HotMelt\PersistentObject::getCachedProperty()
	 */
	public function purgeCachedProperty($key)
	{
		unset($this->{"_$key"});
	}
}