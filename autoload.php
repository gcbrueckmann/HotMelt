<?php
namespace HotMelt;

spl_autoload_register(function ($class) {
	// Map dependency namespace prefixes to subpaths in the lib directory.
	$map = array(
        __NAMESPACE__ => 'Classes',
		'Negotiation' => 'lib/Negotiation/src/Negotiation',
		'ICanBoogie' => function ($prefix, $class) {
			// Inflector relies on its namespaced helper function being defined...
			require_once(dirname(__FILE__).'/lib/Inflector/lib/helpers.php');
			// ...and uses lower-case filenames.
			return 'lib/Inflector/lib/'.strtolower($class).'.php';
		}
	);
	foreach ($map as $name => $pathOrFunction) {
		$prefix = $name.'\\';
		if (substr($class, 0, strlen($prefix)) == $prefix) {
			if (is_callable($pathOrFunction)) {
				$path = call_user_func($pathOrFunction, $prefix, substr($class, strlen($prefix)));
				@include_once(dirname(__FILE__)."/$path");
			} else {
				$filename = str_replace('\\', '/', substr($class, strlen($prefix)));
				@include_once(dirname(__FILE__)."/$pathOrFunction/$filename.php");
			}
		}
	}
});

@include_once(dirname(__FILE__).'/../Site/autoload.php');