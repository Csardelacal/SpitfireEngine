<?php namespace spitfire;

use Psr\Log\LoggerInterface;
use spitfire\core\app\Cluster;
use spitfire\core\Locations;
use spitfire\core\Request;
use spitfire\core\resource\Publisher;
use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\PrivateException;
use spitfire\provider\Container;
use function basedir;

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
	 * @var Container
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
		$this->locations = new Locations();
		
		/*
		 * Initialize the service container, which will manage all the services that
		 * the framework provides to the application.
		 */
		$this->provider = new Container();
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
	
	public static function baseUrl()
	{
		/**
		 * If the application has a url defined as the base url for the application,
		 * we use that.
		 */
		if (config('app.url')) { 
			return config('app.url'); 
		}
		
		/**
		 * CLI applications must have a base url defined, since otherwise the application
		 * could be generating bad URLs without our knowledge. This is usually a very bad 
		 * experience for the user who receives a URL they cannot access.
		 */
		if (php_sapi_name() === 'cli') {
			throw new ApplicationException('CLI applications require the app.url config to be defined', 2104191131);
		}
		
		/**
		 * Poorly configured applications can always fall back to guessing the base url.
		 * This is by no means a good way of handling this.
		 */
		$public = explode('/index.php', $_SERVER['PHP_SELF'], 2)[0];
		return dirname($public);
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
	 * Return the dependency container for this Spitfire instance. This container
	 * allows the application to inject behaviors into the 
	 * 
	 * @return Container
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
