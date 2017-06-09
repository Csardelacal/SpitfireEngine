<?php namespace spitfire\core\router;

/**
 * A route is a class that rewrites a URL path (route) that matches a
 * route or pattern (old_route) into a new route that the system can 
 * use (new_route) to handle the current request.
 * 
 * A Route will only accept Closures, Responses or Paths (including arrays that
 * can be interpreted as Paths by the translation class) as the target.
 * 
 * @todo Define translate class for array to Path translation
 * @todo Define parameter class to replace inside Paths
 * @author César de la Cal <cesar@magic3w.com>
 */
class Redirection extends RewriteRule
{
	
	
	private $reverser;
	
	public function __construct(Routable $server, $pattern, $new_route, $method, $proto = Route::PROTO_ANY) {
		parent::__construct($server, $pattern, $new_route, $method, $proto);
		$this->reverser = null; //TODO: Redirection reverser
	}
	
	/**
	 * 
	 * @return reverser\RouteReverserInterface
	 */
	public function getReverser() {
		return $this->reverser;
	}
	
	/**
	 * 
	 * @param reverser\RouteReverserInterface $reverser
	 * @return Route
	 */
	public function setReverser($reverser) {
		$this->reverser = $reverser;
		return $this;
	}
	
	/**
	 * 
	 * @param type $URI
	 * @param type $method
	 * @param type $protocol
	 * @param type $server
	 * @return string New route
	 */
	public function rewrite($URI, $method, $protocol, $server) {
		return $this->getTarget()->reverse($this->getSource()->test($URI));
	}

}
