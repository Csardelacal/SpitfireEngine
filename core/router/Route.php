<?php namespace spitfire\core\router;

use spitfire\core\router\reverser\RouteReverserInterface;

/**
 * A route is a class that rewrites a URL path (route) that matches a
 * route or pattern (old_route) into a new route that the system can 
 * use (new_route) to handle the current request.
 * 
 * A Route will only accept Closures, Responses or Paths (including arrays that
 * can be interpreted as Paths by the translation class) as the target.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class Route extends RewriteRule
{
	/* These constants are meant for evaluating if a request should be answered 
	 * depending on if the request is done via HTTP(S). This is especially useful
	 * when your application wants to enforce HTTPS for certain requests.
	 */
	const PROTO_HTTP    = 0x01;
	const PROTO_HTTPS   = 0x02;
	const PROTO_ANY     = 0x03;
	
	/* These constants are intended to allow routes to react differently depending
	 * on the METHOD used to issue the request the server is receiving. Spitfire
	 * accepts any of the standard GET, POST, PUT or DELETE methods.
	 */
	const METHOD_GET    = 0x01;
	const METHOD_POST   = 0x02;
	const METHOD_PUT    = 0x04;
	const METHOD_DELETE = 0x08;
	const METHOD_HEAD   = 0x10;
	const METHOD_OPTIONS= 0x20;
	
	/**
	 * 
	 * @param string $URI
	 * @param string $method
	 * @param string $protocol
	 * @param string $extension
	 * @return Parameters
	 */
	public function params($URI, $method, $protocol, string $extension = 'php') 
	{
		return $this->getSource()->test($URI);
	}
	
	/**
	 * 
	 * @param string $URI
	 * @param string $method
	 * @param string $protocol
	 * @param string $extension
	 * @return \spitfire\core\Path|\spitfire\core\Response
	 */
	public function rewrite($URI, $method, $protocol, string $extension = 'php') 
	{
		$params = $this->getSource()->test($URI);
		
		if ($params === null) { return null; }
		
		/*
		 * Closures are the most flexible way to handle requests. They allow to 
		 * determine how the application should react depending on any of the
		 * request's components.
		 */
		return $this->getTarget($params);
		
	}
}
