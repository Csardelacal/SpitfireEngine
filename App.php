<?php namespace spitfire;

use spitfire\core\app\AppAssetsInterface;
use spitfire\core\app\RecursiveAppAssetLocator;
use spitfire\core\Environment;
use spitfire\core\Path;
use spitfire\core\router\Parameters;
use spitfire\core\router\reverser\ClosureReverser;
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
	 * Instances a new application. The application maps a directory where it's residing
	 * with it's name-space and the URL it's serving.
	 * 
	 * Please note that some features need to be 'baked' for the applications to 
	 * properly work (like inline-routes and prebuilt assets). It is recommendable
	 * that the 'baking' is performed automatically on composer::install or similar.
	 * 
	 * @param string $url The URL space the application is intended to be managing
	 * this is used for URL generation, etc
	 */
	public function __construct($url) { 
		$this->url = '/' . trim($url, '\/'); 
	}
	
	/**
	 * Gets the URL space this application is serving. Please note that it's highly
	 * recommended to avoid using nested namespaces since it will often lead to 
	 * broken applications.
	 * 
	 * @return string
	 */
	public function url() { return $this->url; }
	
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

	public function makeRoutes() {
		#Include the routes from the user definitions
		file_exists("{$this->directory()}/settings/routes.php") && include "{$this->directory()}/settings/routes.php";

		#Build some defaults
		$ns = $this->url();
		
		#The default route just returns a path based on app/controller/action/object
		#If your application does not wish this to happen, please override createRoutes
		#with your custome code.
		$default = Router::getInstance()->request($ns, function (Parameters$params, Parameters$server, $extension) {
			$args = $params->getUnparsed();
			return new Path($this, array_shift($args), array_shift($args), $args, $extension);
		});
		
		#The reverser for the default route is rather simple again. 
		#It will concatenate app, controller and action
		$default->setReverser(new ClosureReverser(function (Path$path, $explicit = false) {
			$controller = $path->getController();
			$action     = $path->getAction();
			$object     = $path->getObject();
			
			if ($action     ===        Environment::get('default_action')     && empty($object) && !$explicit)                   { $action     = ''; }
			if ($controller === (array)Environment::get('default_controller') && empty($object) && empty($action) && !$explicit) { $controller = Array(); }
			
			return '/' . trim(implode('/', array_filter(array_merge((array)$controller, [$action], $object))), '/');
		}));
	}
	
}
