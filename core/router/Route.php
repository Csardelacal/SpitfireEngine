<?php namespace spitfire\core\router;

use Closure;
use spitfire\core\Path;
use spitfire\core\router\reverser\RouteReverserFactory;
use Strings;

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
class Route
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
	
	/**
	 * This var holds a reference to a route server (an object containing a pattern
	 * to match virtualhosts) that isolates this route from the others.
	 * 
	 * @var \spitfire\core\router\Routable
	 */
	private $server;
	private $pattern;
	private $patternStr;
	private $newRoute;
	private $parameters;
	private $method;
	private $protocol;
	
	private $reverser;
	
	/**
	 * A route is a pattern Spitfire uses to redirect an URL to something else.
	 * It can 'redirect' (without it being a 302) a request to a new URL, it can
	 * directly send back a response or assign a custom controller, action and 
	 * object to the request.
	 * 
	 * @param \spitfire\core\router\Routable $server The server this route belongs to
	 * @param string $pattern
	 * @param Closure|ParametrizedPath $new_route
	 * @param string $method
	 * @param int    $proto
	 */
	public function __construct(Routable$server, $pattern, $new_route, $method, $proto = Route::PROTO_ANY) {
		$this->server    = $server;
		$this->newRoute  = $new_route;
		$this->method    = $method;
		$this->protocol  = $proto;
		
		$this->reverser  = RouteReverserFactory::make($new_route, $pattern);
		
		$this->patternStr = $pattern;
		$this->pattern    = URIPattern::make($pattern);
	}
	
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
	 * Checks whether a certain method applies to this route. The route can accept
	 * as many protocols as it wants. The protocols are converted to hex integers
	 * and are AND'd to check whether the selected protocol is included in the 
	 * list of admitted ones.
	 * 
	 * @param string|int $method
	 * @return boolean
	 */
	public function testMethod($method) {
		if (!is_numeric($method)) {
			switch ($method){
				case 'GET' :   $method = self::METHOD_GET; break;
				case 'POST':   $method = self::METHOD_POST; break;
				case 'HEAD':   $method = self::METHOD_HEAD; break;
				case 'PUT' :   $method = self::METHOD_PUT; break;
				case 'DELETE': $method = self::METHOD_DELETE; break;
			}
		}
		return $this->method & $method;
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
			$this->patternWalk($this->pattern, $array);
			return true;
		} catch(RouteMismatchException $e) {
			return false;
		}
	}
	
	/**
	 * Tests whether the requested protocol (HTTPS or not) is accepted by this
	 * route. We use once again binary masks to test the protocol. This means that
	 * we can either use HTTP (01), HTTPS (10) or both (11) which will translate
	 * into an integer 1, 2 and 3.
	 *
	 * This way the user can quickly decide whether he wants to use any or both
	 * of them to match a route.
	 * 
	 * @param boolean $protocol
	 * @return boolean
	 */
	public function testProto($protocol) {
		if (!is_int($protocol)) {
			$protocol = ($protocol && $protocol != 'off')? Route::PROTO_HTTPS : Route::PROTO_HTTP;
		}
		return $this->protocol & $protocol;
	}
	
	public function test($URI, $method, $protocol) {
		try {
			return $this->pattern->test($URI) && $this->testMethod($method) && $this->testProto($protocol);
		}
		catch (\spitfire\core\router\RouteMismatchException$e) {
			return false;
		}
	}
	
	protected function rewriteString() {
		$route = $this->getParameters()->replaceInString($this->newRoute);
		
		#If the URL doesn't enforce to be finished pass on the unparsed parameters
		if (!Strings::endsWith($this->newRoute, '/')) {
			$route = rtrim($route, '\/') . '/' . implode('/', $this->getParameters()->getUnparsed());
		}
		
		return '/' . trim($route, '/') . ($this->parameters->getExtension() === 'php'? '/' : '.' . $this->parameters->getExtension());
	}
	
	/**
	 * This method allows the router to use an array as target for the rewriting
	 * instead of another string or path.
	 *
	 * @param Parameters $parameters
	 *
	 * @return Path
	 */
	protected function rewriteArray($parameters) {
		$route = $this->newRoute;
		$path  = new Path(null, null, null, null, $this->parameters->getExtension(), null);
		
		if (isset($route['app']       )) {
			$app = $parameters->getParameter($route['app']);
			$path->setApp($parameters->getParameter($app? $app: $route['app']));
		}
		
		if (isset($route['controller'])) { 
			$controller = $parameters->getParameter($route['controller']);
			$path->setController($controller? $controller : $route['controller']);
		}
		
		if (isset($route['action']))     { 
			$action = $parameters->getParameter($route['action']); 
			$path->setAction( $action? $action : $route['action']);
		}
		
		//TODO: Sometimes several URL fragments should add up to a Object, this is not possible yet 
		if (isset($route['object']))     { $path->setObject(Array($parameters->getParameter($route['object']))); }
		else                             { $path->setObject($parameters->getUnparsed()); }
		
		return $path;
	}
	
	public function rewrite($URI, $method, $protocol, $server) {
		if ($this->test($URI, $method, $protocol)) {
			if (is_string($this->newRoute))         {return $this->rewriteString();}
			if ($this->newRoute instanceof Closure) {return call_user_func_array($this->newRoute, Array($this->parameters, $server->getParameters()));}
			if (is_array($this->newRoute))          {return $this->rewriteArray($server->getParameters()->merge($this->parameters)); }
			if ($this->newRoute instanceof ParametrizedPath) { return $this->newRoute->replace($server->getParameters()->merge($this->pattern->test($URI))); }
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
}
