<?php
/**
 * Defines the `\HotMelt\TemplateView` class.
 */
namespace HotMelt;

/**
 * Provides support for views using the Twig template engine.
 * 
 * @link http://twig.sensiolabs.org Twig Project Website
 */
class TemplateView extends View
{
	/**
	 * Initialize a template view.
	 * 
	 * @param string $contentType The content type to assign to the view.
	 * @param string $templateName The name of the template located in the site templates directory.
	 */
	public function __construct($contentType, $templateName)
	{
		parent::__construct($contentType);
		$this->templateName = $templateName;
	}
	
	/** @ignore */
	public function __toString()
	{
		return '<Template '.$this->templateName.'>';
	}
	
	/** @ignore */
	public function render($data)
	{
		$template = self::twig()->loadTemplate($this->templateName);
		return $template->render($data);
	}
	
	/** @ignore */
	private static $twig;
	
	/**
	 * Return the Twig environment used by `\HotMelt\TemplateView` objects.
	 * 
	 * @return \Twig_Environment
	 */
	public static function twig()
	{
		if (!isset(self::$twig)) {
			require_once dirname(__FILE__).'/../lib/Twig/lib/Twig/Autoloader.php';
			\Twig_Autoloader::register();
			
			$loader = new \Twig_Loader_Filesystem(HOTMELT_SITE_DIRECTORY.'/Templates/');
			$twigEnvironmentOptions = array();
			if (Config::cachedTemplatesDirectory()) {
				$twigEnvironmentOptions['cache'] = Config::cachedTemplatesDirectory();
			}
			self::$twig = new \Twig_Environment($loader, $twigEnvironmentOptions);
		}
		return self::$twig;
	}
}