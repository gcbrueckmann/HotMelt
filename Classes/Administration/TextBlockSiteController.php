<?php
namespace HotMelt\Administration;

class TextBlockSiteController
{
	private static function editableTextBlockManager()
	{
		return new \HotMelt\TextBlockManager(new \HotMelt\PDO(\HotMelt\Config::textBlocksPDO()->dsn), array(\HotMelt\TextBlockManager::CACHE_SETS => false));
	}
	
	public static function setList($request, $route, $variables)
	{
		$textBlocksManager = self::editableTextBlockManager();
		return array('sets' => $textBlocksManager->getSets());
	}
	
	public static function blockList($request, $route, $variables)
	{
		$textBlocksManager = self::editableTextBlockManager();
		return array('set' => $variables['set'], 'blocks' => $textBlocksManager->getBlockNames($variables['set']));
	}
	
	public static function editBlock($request, $route, $variables)
	{
		$textBlocksManager = self::editableTextBlockManager();
		return array('name' => $variables['name'], 'set' => $variables['set'], 'text' => $textBlocksManager->getBlock($variables['name'], $variables['set']));
	}
	
	public static function saveBlock($request, $route, $variables)
	{
		$textBlocksManager = self::editableTextBlockManager();
		return array('statusCode' => 302, 'location' => dirname($_SERVER['REQUEST_URI']).'/edit');
	}
}