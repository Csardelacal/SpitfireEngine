<?php namespace spitfire\core\router;

use Closure;
use Exception;
use spitfire\core\router\reverser\ClosureReverser;
use spitfire\core\router\reverser\RouteReverserInterface;

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
	
	private $parameters;
	
	private $reverser = null;
	
	/**
	 * Tests all the elements of a pattern to see whether the tested route is 
	 * valid or not and to fetch the parameters for it. In case the route and the
	 * URL match we will have an array of parameters in the route that allow us
	 * to customize a request.
	 * 
	 * @throws RouteMismatchException In case the route was not valid.
	 * @param Pattern[] $pattern
	 * @param string[] $array
	 */
	protected function patternWalk($pattern, $array) {
		foreach ($pattern as $p) {
			$this->parameters->addParameters($p->test(array_shift($array)));
		}
		$this->parameters->setUnparsed($array);
	}
	
	/**
	 * Tests if a URL matches the current Route. If so it will return true and you
	 * can use the parameters in it.
	 * 
	 * @param string $URI
	 * @return boolean
	 */
	public function testURI($URI) {
		$array = array_filter(explode('/', $URI));
		
		$this->parameters = new Parameters();
		
		#Check the extension
		$last = explode('.', array_pop($array));
		$this->parameters->setExtension(isset($last[1])? array_pop($last) : 'php');
		array_push($array, implode('.', $last));
		
		try {
			$this->patternWalk($this->getSource(), $array);
			return true;
		} catch(RouteMismatchException $e) {
			return false;
		}
	}
	
	public function rewrite($URI, $method, $protocol, $server) {
		if ($this->test($URI, $method, $protocol)) {
			if ($this->getTarget() instanceof Closure) {return call_user_func_array($this->getTarget(), Array($this->parameters, $server->getParameters()));}
			if ($this->getTarget() instanceof ParametrizedPath) { return $this->getTarget()->replace($server->getParameters()->merge($this->getSource()->test($URI))); }
		}
		return false;
	}
	
	public function getParameters($keys = false) {
		if (!$keys) { return $this->parameters; }
		
		$array = array_keys($this->parameters);
		array_walk($array, function(&$e) {$e = ':' . $e;});
		return $array;
	}
	
	/**
	 * 
	 * @return RouteReverserInterface
	 */
	public function getReverser() {
		if ($this->reverser || !$this->getTarget() instanceof ParametrizedPath) { return $this->reverser; }

		return $this->reverser = new ClosureReverser(function ($path) {
			try { return $this->getSource()->reverse($this->getTarget()->extract($path)); } 
			catch (Exception$e) { return false; }
		});

	}
	
	/**
	 * 
	 * @param RouteReverserInterface $reverser
	 * @return Route
	 */
	public function setReverser($reverser) {
		$this->reverser = $reverser;
		return $this;
	}
}
