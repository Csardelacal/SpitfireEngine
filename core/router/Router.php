<?php namespace spitfire\core\router;

use spitfire\core\Path;
use spitfire\core\Response;

/**
 * Routers are tools that allow your application to listen on alternative urls and
 * attach controllers to different URLs than they would normally do. Please note
 * that enabling a route to a certain controller does not disable it's canonical
 * URL.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class Router extends Routable
{
	
	
	/**
	 * This rewrites a request into a Path (or in given cases, a Response). This 
	 * allows Spitfire to use the data from the Router to accordingly find a 
	 * controller to handle the request being thrown at it.
	 * 
	 * Please note that Spitfire is 'lazy' about it's routes. Once it found a valid
	 * one that can be used to respond to the request it will stop looking for
	 * another possible rewrite.
	 * 
	 * @todo The extension should be passed down to the servers (and therefore 
	 * the routes) to allow the routes to respond to different requests properly.
	 * 
	 * @param string $route
	 * @param string $method
	 * @param string $protocol
	 * @return Path|Response
	 */
	public function rewrite ($url, $method, $protocol) 
	{
		
		#Combine routes from the router and server
		$routes = $this->getRoutes()->toArray();
		
		if (\spitfire\utils\Strings::endsWith($url, '/')) {
			$url     = pathinfo($url, PATHINFO_DIRNAME) . '/' . pathinfo($url, PATHINFO_BASENAME);
			$ext     = 'php';
		} 
		else {
			$ext     = pathinfo($url, PATHINFO_EXTENSION);
			$url     = pathinfo($url, PATHINFO_DIRNAME) . '/' . pathinfo($url, PATHINFO_FILENAME);
		}
		
		#Test the routes
		foreach ($routes as $route) { /*@var $route Route*/
			
			#Verify whether the route is valid at all
			if (!$route->test($url, $method, $protocol)) { continue; }
			
			#Check whether the route can rewrite the request
			$rewrite = $route->rewrite($url, $method, $protocol, $ext);

			if ( $rewrite instanceof Path || $rewrite instanceof Response) { return $rewrite; }
			if ( $rewrite !== false)         { $url = $rewrite; }
		}
		
		#Implicit else.
		return false;
		throw new \spitfire\exceptions\PublicException('No such route', 404);
	}
	
	/**
	 * Allows the router to act with a singleton pattern. This allows your app to
	 * share routes across several points of it.
	 * 
	 * @staticvar Router $instance
	 * @return Router
	 */
	public static function getInstance() {
		static $instance = null;
		if ($instance) { return $instance; }
		else           { return $instance = new Router(); }
	}

}
