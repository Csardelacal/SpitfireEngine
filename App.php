<?php namespace spitfire;

use spitfire\core\router\Router;

/**
 * Spitfire Application Class. This class is the base of every other 'app', an 
 * app is a wrapper of controllers (this allows to plug them into other SF sites)
 * that defines a set of rules to avoid collisions with the rest of the apps.
 * 
 * Every app resides inside of a namespace, this externally defined variable
 * defines what calls Spitfire redirects to the app.
 * 
 * @author César de la Cal<cesar@magic3w.com>
 */
abstract class App
{
	
	/**
	 * 
	 * @var Router
	 */
	private $router;
	
	/**
	 * Instances a new application. The application maps a directory where it's residing
	 * with it's name-space and the URL it's serving.
	 * 
	 * Please note that some features need to be 'baked' for the applications to 
	 * properly work (like inline-routes and prebuilt assets). It is recommendable
	 * that the 'baking' is performed automatically on composer::install or similar.
	 * 
	 * @param Router $router A scoped router for this application to write it's routes
	 * to.
	 */
	public function __construct($router)
	{ 
		$this->router = $router; 
	}
	
	/**
	 * Gets the URL space this application is serving. Please note that it's highly
	 * recommended to avoid using nested namespaces since it will often lead to 
	 * broken applications.
	 * 
	 * @return string
	 */
	public function url()
	{
		return $this->router->getPrefix(); 
	}
	
	/**
	 * Returns the directory this application is watching.
	 * 
	 * @return string The directory the app resides in and where it's controllers, models, etc directories are located
	 */
	abstract public function directory();
	
	/**
	 * Returns the application's class-namespace. This is the namespace in which
	 * spitfire will look for controllers, models etc for this application.
	 */
	abstract public function namespace();
}
