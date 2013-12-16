<?php
/**
 * Main entry point for a website using HotMelt.
 * 
 * Redirect requests to this file to have them processed by HotMelt.
 */
namespace HotMelt;

error_reporting(E_ALL);

require_once(dirname(__FILE__).'/autoload.php');

Log::setLevel(Config::logLevel());

if (Config::timezone()) {
	Log::info('Applying timezone '.Config::timezone());
	date_default_timezone_set(Config::timezone());
}

@include_once(dirname(__FILE__).'/../Site/init.php');

$request = Request::HTTPServerRequest();
$route = Route::find($request);
if ($route === false && substr($request->redirectURL, strlen($request->redirectURL) - 1) != '/') {
	$alternateURI = $request->redirectURL.'/';
	Log::info("No route for $request -- trying alternate URI $alternateURI");
	if (Route::find($alternateURI) !== false) {
		$redirectURL = $request->rootURL.$alternateURI;
		if (!empty($request->queryString)) {
			$redirectURL .= '?'.$request->queryString;
		}
		$response = new Response(301, array('Location' => $redirectURL, 'Content-Type' => 'text/plain; charset=UTF-8'), "Redirecting to $redirectURL...");
		$response->send();
		exit();
	}
}

try {
	$view = null;
	if (!$route) {
		throw new HTTPErrorException(404, 'No route for '.$request->redirectURL.'.');
	}
	if (!$route->accepts_method($request->requestMethod)) {
		throw new HTTPErrorException(405, 'Method '.$request->requestMethod.' is not acceptable for '.$request->redirectURL.'.');
	}
	Log::info("Using $route for $request.");
	$data = array('_request' => $request);
	$data = array_merge($data, Middleware::executeHook('preAction', $route->action, $request, $route));
	Log::info("Executing action for $route.");
	$data = array_merge($data, $route->action->perform($request, $route));
	$view = $route->negotiateView($request);
	Log::info("Rendering $view via $route.");
	$body = $view->render($data);
	$response = new Response($view->statusCode, $view->headers, $body);
} catch (HTTPErrorException $exception) {
	Log::warning("Encountered an exception while handling $request: $exception");
	try {
		$route = Route::error();
		if (!$route) {
			throw new \Exception(
<<<EOS
No error route found to handle $exception.

You can set up an error route with \HotMelt\Route::error() to gracefully handle this situation.
EOS
				);
		}
		Middleware::executeHook('preAction', $route->action, $request, $route);
		$data = $route->action->performForException($request, $route, $exception);
		$view = $route->negotiateView($request);
		$body = $view->render($data);
		$response = new Response($exception->statusCode, array_merge($exception->headers, $view->headers), $body);
	} catch (\Exception $exception) {
		Log::error("Encountered an exception while handling an exception originally encountered while handling $request: $exception");
		$response = new Response(500, array('Content-Type' => 'text/plain; charset=UTF-8'), "UNHANDLED EXCEPTION\n\n".$exception->getMessage());
	}
}
$response->send();