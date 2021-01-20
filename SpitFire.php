<?php namespace spitfire;

use Psr\Log\LoggerInterface;
use spitfire\App;
use spitfire\core\app\AppNotFoundException;
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
	
	/**
	 *
	 * @var Provider
	 */
	private $provider;
	
	/**
	 * Provides logging capabilities for the applications running within Spitfire.
	 * You can select a logging mechanism by adding a PSR\log compatible logger
	 * to the dependency injection file.
	 *
	 * @var LoggerInterface 
	 */
	private $log;
	private $apps = Array();
	
	public function __construct() {
		#Check if SF is running
		if (self::$started) { throw new PrivateException('Spitfire is already running'); }
		self::$started = true;
		
		$this->provider = new Provider();
		
	}

	public function fire() {
		
		#Try to include the user's evironment & routes
		#It'd be interesting to move these to the index.php file of applications that
		#wish to implement these features.
		$overrides = [
			basedir() . 'bin/settings/environments.php',
			basedir() . 'bin/settings/routes.php'
		];
		
		foreach ($overrides as $file) {
			file_exists($file) && include($file);
		}
		
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
	 * @return SpitFire
	 */
	public function app(App $app) {
		$this->apps[] = $app;
		return $this;
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
	 * @param string|App $namespace
	 * @return App
	 */
	public function getApp($namespace) {
		
		/*
		 * If the app we passed happens to be an App object, we can immediately return
		 * the object. Applications are (at least in theory) only able to be registered
		 * with the single Spitfire object.
		 */
		if ($namespace instanceof App) {
			return $namespace;
		}
		
		/*
		 * Loop over the applications and find the one registered for the current
		 * namespace.
		 */
		$_ret = collect($this->apps)->filter(function ($e) use ($namespace) { return $e->url() === $namespace; })->rewind();
		
		if ($_ret) { return $_ret; }
		elseif ($namespace == '') { return new UnnamedApp(''); }
		else { throw new AppNotFoundException(sprintf('No app found for %s.', $namespace)); }
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
