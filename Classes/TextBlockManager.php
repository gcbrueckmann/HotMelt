<?php
/**
 * Defines the deprecated `\HotMelt\TextBlockManager` class.
 */
namespace HotMelt;

/**
 * A deprecated way to implement dynamic text on web pages.
 * 
 * @deprecated 1.1.0 Deprecated since HotMelt 1.0.0.
 */
class TextBlockManager
{
	/** @ignore */
	const TABLE_NAME    = 'tableName';
	/** @ignore */
	const DEFAULT_TABLE = 'hm_text_blocks';
	/** @ignore */
	const PRELOAD_SETS  = 'preload_sets';
	/** @ignore */
	const CACHE_SETS    = 'cache_sets';
	
	/** @ignore */
	private $_options;
	
	/** @ignore */
	public function __construct($PDO, $options = null)
	{
		$this->PDO = $PDO;
		$this->_options = $options ? $options : array();
	}
	
	/** @ignore */
	public function option($key)
	{
		if (isset($this->_options[$key])) {
			return $this->_options[$key];
		}
		if ($key == self::TABLE_NAME) {
			return self::DEFAULT_TABLE;
		} elseif ($key == self::PRELOAD_SETS) {
			return false;
		} elseif ($key == self::CACHE_SETS) {
			return true;
		}
		throw new \Exception("Invalid option ($key).");
	}
			
	/** @ignore */
	private static $blockSets = array();
	
	/** @ignore */
	public function getBlock($name, $set, $description = null)
	{
		if (!$this->option(self::CACHE_SETS)) {
			$statement = $this->PDO->prepare("SELECT text
			                             FROM `".$this->PDO->escape($this->option(self::TABLE_NAME))."`
			                             WHERE `set` = :set
			                             AND `name` = :name
			                             LIMIT 1");
			$cursor = $statement->execute(array(':set' => $set, ':name' => $name)) ? $statement : false;
			if (($block = $cursor->fetchObject()) === false) {
				return false;
			}
			return $block->text;
		} else {
			if (!isset(self::$blockSets[$set])) {
				$statement = $this->PDO->prepare("SELECT *
				                             FROM `".$this->PDO->escape($this->option(self::TABLE_NAME))."`
				                             WHERE `set` = :set
				                             ORDER BY name ASC");
				$cursor = $statement->execute(array(':set' => $set)) ? $statement : false;
				$blocks = array();
				while (($block = $cursor->fetchObject()) !== false) {
					$blocks[$block->name] = $block->text;
				}
				self::$blockSets[$set] = $blocks;
			}
			if (!isset(self::$blockSets[$set][$name])) {
				return "[[MISSING TEXT BLOCK: $name]]";
			}
			return self::$blockSets[$set][$name];
		}
	}
	
	/** @ignore */
	public function getSets()
	{
		$statement = $this->PDO->prepare("SELECT `set`
		                             FROM `".$this->PDO->escape($this->option(self::TABLE_NAME))."`
		                             ORDER BY `set` ASC");
		$cursor = $statement->execute() ? $statement : false;
		$sets = array();
		while (($row = $cursor->fetchObject()) !== false) {
			$sets[] = $row->set;
		}
		return $sets;
	}
	
	/** @ignore */
	public function getBlockNames($set)
	{
		$statement = $this->PDO->prepare("SELECT name, description
		                             FROM `".$this->PDO->escape($this->option(self::TABLE_NAME))."`
		                             WHERE `set` = :set
		                             ORDER BY `name` ASC");
		$cursor = $statement->execute(array(':set' => $set)) ? $statement : false;
		$blocks = array();
		while (($row = $cursor->fetchObject()) !== false) {
			$blocks[$row->name] = $row->description;
		}
		return $blocks;
	}
}