<?php
/**
 * Defines the `\HotMelt\PersistentObjectQuery` class.
 */
namespace HotMelt;

/**
 * Provides an interface for querying persistent objects.
 * 
 * The preferred way to create query objects is to use the `objectsWith...()` automagic method of the `PersistentObject` class.
 */
class PersistentObjectQuery
{
	/** @ignore */
	public function __construct($persistentObjectClass, $queryString, $arguments, $pdo=null)
	{
		$this->_persistentObjectClass = $persistentObjectClass;
		$this->_queryString = $queryString;
		$this->_arguments = $arguments;
		$this->_pdo = $pdo;
	}
	
	/** @ignore */
	private function limitClauseForRange($range)
	{
		if ($range === false) {
			return '';
		} elseif (is_array($range)) {
			assert(count($range) == 2);
			return 'LIMIT '.$range[0].','.$range[1];
		} else {
			return "LIMIT $range";
		}
	}
	
	/** @ignore */
	private function sortingClauseForSpec($spec)
	{
		if ($spec === false) {
			return '';
		} elseif (strpos($spec, '+') === 0) {
			return 'ORDER BY '.$pdo->quoteIdentifier(substr($spec, 1)).' ASC';
		} elseif (strpos($spec, '-') === 0) {
			return 'ORDER BY '.$pdo->quoteIdentifier(substr($spec, 1)).' DESC';
		} else {
			return 'ORDER BY '.$pdo->quoteIdentifier($spec).' ASC';
		}
	}
	
	const OPERATOR_EQUAL_TO = 'EqualTo';
	const OPERATOR_NOT_EQUAL_TO = 'NotEqualTo';
	const OPERATOR_LESS_THAN = 'LessThan';
	const OPERATOR_LESS_THAN_OR_EQUAL_TO = 'LessThanOrEqualTo';
	const OPERATOR_GREATER_THAN = 'GreaterThan';
	const OPERATOR_GREATER_THAN_OR_EQUAL_TO = 'GreaterThanOrEqualTo';
	const OPERATOR_CONTAINS = 'Contains';
	const OPERATOR_BEGINS_WITH = 'BeginsWith';
	const OPERATOR_ENDS_WITH = 'EndsWith';
	
	/** @ignore */
	private function whereClause()
	{
		$whereClauseComponents = array();
		$inflector = \ICanBoogie\Inflector::get();
		$queryComponents = preg_split('/(And|Or)(?!EqualTo)/', $this->_queryString, null, PREG_SPLIT_DELIM_CAPTURE|PREG_SPLIT_NO_EMPTY);
		if (empty($queryComponents)) {
			$queryComponents = array($this->_queryString);
		}
		foreach ($queryComponents as $component) {
			if (strcmp($component, 'And') == 0) {
				$whereClauseComponents[] = 'AND';
			} elseif (strcmp($component, 'Or') == 0) {
				$whereClauseComponents[] = 'OR';
			} else {
				$operators = array(
					self::OPERATOR_EQUAL_TO => '=',
					self::OPERATOR_NOT_EQUAL_TO => '!=',
					self::OPERATOR_LESS_THAN => '<',
					self::OPERATOR_LESS_THAN_OR_EQUAL_TO => '<=',
					self::OPERATOR_GREATER_THAN => '<',
					self::OPERATOR_GREATER_THAN_OR_EQUAL_TO => '<=',
					self::OPERATOR_CONTAINS => false,
					self::OPERATOR_BEGINS_WITH => false,
					self::OPERATOR_ENDS_WITH => false
				);
				$key = $component;
				$operator = self::OPERATOR_EQUAL_TO;
				foreach (array_keys($operators) as $operatorSuffix) {
					if (($operatorOffset = strrpos($component, $operatorSuffix)) !== false) {
						$key = substr($component, 0, $operatorOffset);
						$operator = $operatorSuffix;
					}
				}
				$key = $inflector->camelize($key, true);
				switch ($operator) {
					case self::OPERATOR_CONTAINS:
						$whereClauseComponents[] = "$key LIKE ('%%' || ? || '%%')";
						break;
					case self::OPERATOR_BEGINS_WITH:
						$whereClauseComponents[] = "$key LIKE (? || '%%')";
						break;
					case self::OPERATOR_ENDS_WITH:
						$whereClauseComponents[] = "$key LIKE ('%%' || ?)";
						break;
					default:
						$whereClauseComponents[] = "$key {$operators[$operator]} ?";
						break;
				}
			}
		}
		return implode(' ', $whereClauseComponents);
	}
	
	/**
	 * Executes a query and returns the result.
	 * 
	 * @param mixed $limit An integer indicating the maximum number of objects to fetch or an array indicating the offset and number of objects.
	 * @param string $sorting A key name indicating the property to sort objects by. Prefixing the key name with `'+'` or `'-'` will sort in ascending or descending order, respectively. The default is to sort in ascending order.
	 * @return mixed An array of PersistentObject instances, or `false` if the query could not be executed.
	 */
	public function fetch($limit = false, $sorting = false)
	{
		if (($pdo = $this->_pdo) === null) {
			assert(($pdo = call_user_func("{$this->_persistentObjectClass}::defaultPDO")) !== null);
		}
		$tableName = call_user_func("{$this->_persistentObjectClass}::tableName");
		$limit = $this->limitClauseForRange($limit);
		$sorting = $this->sortingClauseForSpec($sorting);
		$whereClause = $this->whereClause();
		$statement = $pdo->prepare("SELECT * FROM ".$pdo->quoteIdentifier($tableName)." WHERE $whereClause $sorting $limit");
		if (!$statement->execute($this->_arguments)) {
			return false;
		}
		$results = array();
		while (($obj = $statement->fetchObject($class)) !== false) {
			$results[] = $obj;
		}
		if ($limit === false) {
			$this->_count = count($results);
		}
		return $results;
	}
	
	/**
	 * Executes a query and returns the number of results.
	 *
	 * @param mixed $limit An integer indicating the maximum number of objects to count or an array indicating the offset and number of objects.
	 * @return mixed An integer indicating the number of results, or `false` if the query could not be executed.
	 */
	public function count($limit = false)
	{
		if ($limit === false && isset($this->_count)) {
			return $this->_count;
		}
		if (($pdo = $this->_pdo) === null) {
			assert(($pdo = call_user_func("{$this->_persistentObjectClass}::defaultPDO")) !== null);
		}
		$tableName = call_user_func("{$this->_persistentObjectClass}::tableName");
		$limit = $this->limitClauseForRange($limit);
		$whereClause = $this->whereClause();
		$statement = $pdo->prepare("SELECT COUNT(*) AS rowCount FROM ".$pdo->quoteIdentifier($tableName)." WHERE $whereClause $limit");
		if (!$statement->execute($this->_arguments)) {
			return false;
		}
		if (($result = $statement->fetchObject()) === false) {
			return false;
		}
		if ($limit !== false) {
			return intval($result->rowCount);
		}
		return ($this->_count = intval($result->rowCount));
	}
}