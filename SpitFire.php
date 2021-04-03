<?php namespace spitfire;

use Psr\Log\LoggerInterface;
use spitfire\App;
use spitfire\core\app\AppNotFoundException;
use spitfire\core\app\Cluster;
use spitfire\core\Context;
use spitfire\core\ContextCLI;
use spitfire\core\Environment;
use spitfire\core\Locations;
use spitfire\core\Request;
use spitfire\core\resource\Publisher;
use spitfire\core\resource\PublisherDirector;
use spitfire\core\Response;
use spitfire\exceptions\PrivateException;
use spitfire\provider\Container;
use spitfire\provider\Provider;
use spitfire\utils\Strings;
use function basedir;
use function collect;
use function current_context;

/**
 * Dispatcher class of Spitfire. Calls all the required classes for Spitfire to run.
 * 
 * @author César de la Cal <cesar@magic3w.com>
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
	 * Provides quick access to different locations that the system will commonly
	 * use. This object is read only.
	 * 
	 * @var Locations
	 */
	private $locations;
	
	/**
	 *
	 * @var Publisher
	 */
	private $publisher;
	
	/**
	 * Provides logging capabilities for the applications running within Spitfire.
	 * You can select a logging mechanism by adding a PSR\log compatible logger
	 * to the dependency injection file.
	 *
	 * @var LoggerInterface 
	 */
	private $log;
	
	/**
	 * 
	 * @var Cluster
	 */
	private $apps;
	
	public function __construct() {
		#Check if SF is running
		if (self::$started) { throw new PrivateException('Spitfire is already running'); }
		self::$started = true;

		$this->apps = new Cluster();
		$this->publisher = new Publisher();
		
		/*
		 * Initialize the service container, which will manage all the services that
		 * the framework provides to the application.
		 */
		$this->provider = new Container();
		$loaded = [];
		
		/*
		 * Instance all the service providers and call the register method, this
		 * allows them to bind all the services they provide.
		 */
		foreach($this->config('app.providers') as $name) {
			$provider = new $name($this->provider);
			$provider->register();
			$loaded[] = $provider;
		}
		
		/*
		 * Each provider is allowed to invoke a start method, which it can then use
		 * to register resources and further services (after all the  service providers
		 * had a chance to register the services they provide).
		 */
		foreach ($loaded as $provider) {
			$provider->init();
		}
		
		$this->enable();
	}

	public function fire() {
		
		#Import the apps
		include $this->locations->config() . 'apps.php';
		
		#TODO: This needs to be moved to service providers
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
		
		/*
		 * Load the appropriate kernel for the user's intent. This is the main 'fork'
		 * in spitfire's logic. 
		 */
		if (php_sapi_name() === 'cli') {
			$kernel = $this->provider->get(\spitfire\core\kernel\ConsoleKernel::class);
			$kernel->boot();
			
			/*
			 * The kernel needs to know which command to execute, the command will 
			 * be written to argv[1]. From there on, the kernel can take over, arrange
			 * the arguments so they make sense to the command, and pass over control
			 * to the director that executes the script.
			 */
			$_ret = $kernel->exec($argv[1], array_slice($argv, 2));
			exit($_ret);
		}
		else {
			$kernel = $this->provider->get(\spitfire\core\kernel\WebKernel::class);
			$kernel->boot();
			
			/*
			 * Generate a request object that allows the kernel to work with an
			 * abstraction of the real request.
			 */
			$response = $kernel->process(Request::fromServer());
			$response->send();
		}
		
		
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
	
	/**
	 * Returns the collection containing the apps that spitfire has registered.
	 * You can use the collection to query for a certain application by their
	 * url or classpath.
	 * 
	 * @return Cluster
	 */
	public function apps() {
		return $this->apps;
	}
	
	public static function baseUrl(){
		if (Environment::get('base_url')) { return Environment::get('base_url'); }
		if (php_sapi_name() === 'cli')    { return '/'; }
		
		list($base_url) = explode('/index.php', $_SERVER['PHP_SELF'], 2);
		return $base_url;
	}
	
	/**
	 * 
	 * @return Locations
	 */
	public function locations() {
		return $this->locations;
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
		
		#Define the current timezone
		date_default_timezone_set(Environment::get('timezone'));
                
		#Set the display errors directive to the value of debug
		ini_set("display_errors" , Environment::get('debug_mode')? '1' : '0');
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
	
	/**
	 * @todo This method needs to lazy load configuration from the appropriate files and
	 * so on, a helper for this would probably make sense
	 * 
	 * @param type $path
	 * @return type
	 */
	public function config($path) {
		return [];
	}	
	
	/**
	 * Returns the publisher for spitfire. This object allows the applications to
	 * suggest pablishing resources to certain shared resources.
	 * 
	 * @return Publisher
	 */
	public function publisher() {
		return $this->publisher;
	}

}
