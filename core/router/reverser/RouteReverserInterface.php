<?php namespace spitfire\core\router\reverser;

/**
 * A route reverser allows any route to provide a mechanism to reverse any given
 * route. This allows the application to build URLs that match the route they'll
 * be guided through.
 */
interface RouteReverserInterface
{
	
	/**
	 * The reverse method allows a route to provide a method to construct a path 
	 * that will then be used by the URL string builder.
	 * 
	 * @param string   $app
	 * @param string[] $controller 
	 * @param string   $action
	 * @param string[] $object
	 * @param mixed[]  $additional Custom parameters the user may wish to provide
	 * 
	 * @return string|boolean A string containing the desired path / bool(false)
	 *         to indicate that the route does not accept this method.
	 */
	function reverse($app, $controller, $action, $object, $additional = Array());
}
