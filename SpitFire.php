<?php namespace spitfire;

use spitfire\App;
use spitfire\core\app\AppAssetsInterface;
use spitfire\core\app\RecursiveAppAssetLocator;
use spitfire\core\Context;
use spitfire\core\Environment;
use spitfire\core\Request;
use spitfire\core\Response;
use spitfire\exceptions\PrivateException;
use spitfire\provider\Provider;
use spitfire\utils\Strings;
use function basedir;
use function collect;
use function current_context;
use function debug;

/**
 * Dispatcher class of Spitfire. Calls all the required classes for Spitfire to run.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 * @package spitfire
 */
class SpitFire
{
	
	static  $started = false;
	
	private $request;
	private $provider;
	private $apps = Array();
	
	public function __construct() {
		#Check if SF is running
		if (self::$started) { throw new PrivateException('Spitfire is already running'); }
		self::$started = true;
		
		$this->provider = new Provider();
		$this->enable();
	}

	public function fire() {
		
		#Import the apps
		include CONFIG_DIRECTORY . 'apps.php';
		
		#Check if there is a defualt app to receive calls to /
		if (!collect($this->apps)->filter(function (App$e) { return !$e->url(); })->rewind()) {
			$this->apps[] = new UnnamedApp('');
		}
		
		#Every app now gets the chance to create appropriate routes for it's operation
		foreach ($this->apps as $app) { $app->makeRoutes(); }
		
		#Get the current path...
		$request = $this->request = Request::fromServer();
		
		#If the developer responded to the current route with a response we do not need 
		#to handle the request
		if ($request instanceof Response) {
			return $request->getPath()->send();
		}
		
		#Start debugging output
		ob_start();

		#If the request has no defined controller, action and object it will define
		#those now.
		$path    = $request->getPath();

		#Receive the initial context for the app. The controller can replace this later
		/*@var $initContext Context*/
		$initContext = ($path instanceof Context)? $path : $request->makeContext();

		#Define the context, include the application's middleware configuration.
		current_context($initContext);
		include CONFIG_DIRECTORY . 'middleware.php';

		#Get the return context
		/*@var $context Context*/
		$context = $initContext->run();

		#End debugging output
		$context->view->set('_SF_DEBUG_OUTPUT', ob_get_clean());

		#Send the response
		$context->response->send();
		
	}
	
	/**
	 * Set / Get applications from Spitfire. The software you write can use this
	 * to communicate with the applications.
	 * 
	 * @param string $uri
	 * @param App $app
	 * @return App
	 * @throws PrivateException If the application was not found
	 * @todo Move towards a app collection so Spitfire becomes apps + extensions
	 * @todo Introduce AppNotFound exception
	 */
	public function app($uri, App $app = null) {
		if (!Strings::startsWith($uri, '/')) { $uri.= '/'; }
		
		if ($app) { $this->apps[$uri] = $app; }
		if (!isset($this->apps[$uri])) { throw new PrivateException(sprintf('No app defined for %s', $uri)); }
		
		return $this->apps[$uri];
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev
	 * @param type $app
	 * @param type $namespace
	 */
	public function registerApp($app) {
		$this->apps[] = $app;
	}
	
	public function apps() {
		return $this->apps;
	}
	
	public function appExists($namespace) {
		if (!is_string($namespace)) { return false; }
		return collect($this->apps)->filter(function ($e) use ($namespace) { return $e->url() == $namespace; })->rewind();
	}
	
	public function findAppForClass($name) {
		if (empty($this->apps)) {
			return $this;
		}
		
		/*@var $app App*/
		foreach($this->apps as $app) {
			if (Strings::startsWith($name, $app->namespace())) {
				return $app;
			}
		}
		return $this;
	}
	
	/**
	 * 
	 * @param string $namespace
	 * @return App
	 */
	public function getApp($namespace) {
		return collect($this->apps)->filter(function ($e) use ($namespace) { return $e->url() === $namespace; })->rewind();
	}
	
	public static function baseUrl(){
		if (Environment::get('base_url')) { return Environment::get('base_url'); }
		if (php_sapi_name() === 'cli')    { return '/'; }
		
		list($base_url) = explode('/index.php', $_SERVER['PHP_SELF'], 2);
		return $base_url;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20190527
	 * @see debug
	 * @param string $msg
	 * @return string
	 */
	public function log($msg) {
		debug()->log($msg);
		return $msg;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20190527
	 * @see debug
	 * @param type $msg
	 * @return string[]
	 */
	public function getMessages() {
		return debug()->getMessages();
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20190527
	 * @return type
	 */
	public function getCWD() {
		return basedir();
	}
	
	/**
	 * Contents need to be moved somewhere else. This function is no longer valid
	 * due to the fact that spitfire is no longer an app.
	 * 
	 * @deprecated since version 0.1-dev 20201012
	 */
	public function enable() {

		#Try to include the user's evironment & routes
		ClassInfo::includeIfPossible(CONFIG_DIRECTORY . 'environments.php');
		ClassInfo::includeIfPossible(CONFIG_DIRECTORY . 'routes.php');
		
		/*
		 * Include the config for the dependency injection.
		 */
		ClassInfo::includeIfPossible(CONFIG_DIRECTORY . 'provider.php');
		
		#Define the current timezone
		date_default_timezone_set(Environment::get('timezone'));
                
		#Set the display errors directive to the value of debug
		ini_set("display_errors" , Environment::get('debug_mode')? '1' : '0');
	}
	
	/**
	 * Returns the current request.
	 * 
	 * @return Request The current request
	 */
	public function getRequest() {
		return $this->request;
	}
	
	public function assets() : AppAssetsInterface {
		return new RecursiveAppAssetLocator($this->getCWD() . '/assets/src/');
	}
	
	/**
	 * Return the dependency container for this Spitfire instance. This container
	 * allows the application to inject behaviors into the 
	 * 
	 * @return Provider
	 */
	public function provider() {
		return $this->provider;
	}

}
