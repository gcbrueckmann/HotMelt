<?php
namespace HotMelt;

class TemplateView extends View
{
	public function __construct($contentType, $templateName)
	{
		parent::__construct($contentType);
		$this->templateName = $templateName;
	}
	
	public function __toString()
	{
		return '<Template '.$this->templateName.'>';
	}
	
	public function render($data)
	{
		$template = self::twig()->loadTemplate($this->templateName);
		return $template->render($data);
	}
	
	private static $twig;
	public static function twig()
	{
		if (!isset(self::$twig)) {
			require_once dirname(__FILE__).'/../lib/Twig/lib/Twig/Autoloader.php';
			\Twig_Autoloader::register();
			
			$loader = new \Twig_Loader_Filesystem(dirname(__FILE__).'/../../Site/Templates/');
			$twigEnvironmentOptions = array();
			if (Config::cachedTemplatesDirectory()) {
				$twigEnvironmentOptions['cache'] = Config::cachedTemplatesDirectory();
			}
			
			self::$twig = new \Twig_Environment($loader, $twigEnvironmentOptions);
			if (Config::textBlocksPDO()) {
				$function = new \Twig_SimpleFunction('dynamic_text', function ($name, $set, $description) {
					return TemplateView::textBlockManager()->getBlock($name, $set, $description);
				});
				self::$twig->addFunction($function);
			}
		}
		return self::$twig;
	}
	
	private static $textBlockManager;
	public static function textBlockManager()
	{
		if (!isset(self::$textBlockManager)) {
			self::$textBlockManager = new TextBlockManager(Config::textBlocksPDO());
		}
		return self::$textBlockManager;
	}
}