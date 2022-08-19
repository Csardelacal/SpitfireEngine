<?php namespace spitfire;

use spitfire\contracts\core\LocationsInterface;
use spitfire\core\Locations;
use spitfire\exceptions\ApplicationException;
use spitfire\provider\Container;

/**
 * Dispatcher class of Spitfire. Calls all the required classes for Spitfire to run.
 *
 * @author César de la Cal <cesar@magic3w.com>
 */
class SpitFire
{
	
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
	
	public function __construct()
	{
		
		$this->locations = new Locations(defined('BASEDIR')? rtrim(BASEDIR, '\/') : __DIR__);
		
		/*
		 * Initialize the service container, which will manage all the services that
		 * the framework provides to the application.
		 */
		$this->provider = new Container();
		$this->provider()->set(Locations::class, $this->locations);
		$this->provider()->set(LocationsInterface::class, $this->locations);
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
		if (cli()) {
			throw new ApplicationException('CLI applications require the app.url config to be defined', 2104191131);
		}
		
		/**
		 * Poorly configured applications can always fall back to guessing the base url.
		 * This is by no means a good way of handling this.
		 */
		$public = explode('/public/index.php', $_SERVER['PHP_SELF'], 2)[0];
		return dirname($public);
	}
	
	/**
	 *
	 * @return Locations
	 */
	public function locations()
	{
		return $this->locations;
	}
	
	/**
	 * Return the dependency container for this Spitfire instance. This container
	 * allows the application to inject behaviors into the
	 *
	 * @return Container
	 */
	public function provider()
	{
		return $this->provider;
	}
}
