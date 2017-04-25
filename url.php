<?php

use spitfire\SpitFire;
use spitfire\core\Request;
use spitfire\io\Get;
use spitfire\exceptions\PrivateException;
use spitfire\environment;

/**
 * 
 * This dinamically generates system urls this allows us to validate URLs if needed
 * or generate different types of them depending on if pretty links is enabled
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class URL implements ArrayAccess
{
	/**
	 * @var \spitfire\core\Path Contains information about the controller / action
	 * / object combination that will be used for this URL.
	 */
	private $path;
	
	/**
	 * @var mixed|Get[] Contains data about the _GET parameters this URL will pass
	 * to the system if invoked by the user.
	 */
	private $params = Array();
	
	/**
	 * This static method allows your application to provide a custom serializer
	 * for your app. This is useful if you're using a custom routing system that 
	 * does not comply with the standard controller/action/object format.
	 * 
	 * @deprecated since version 0.1-dev In favor of Route reversers
	 * @var Closure
	 */
	private static $serializer = null;

	/** @var string */
	private $extension;

	/** @var \spitfire\App */
	private $app;

	/**
	 * Creates a new URL. Use this class to generate dynamic URLs or to pass
	 * URLs as parameters. For consistency (double base prefixes and this
	 * kind of misshaps aren't funny) use this object to pass or receive URLs
	 * as paramaters.
	 * 
	 * Please note that when passing a URL that contains the URL as a string like
	 * "/hello/world?a=b&c=d" you cannot pass any other parameters. It implies that
	 * you already have a full URL.
	 * 
	 * You can pass any amount of parameters to this class,
	 * the constructor will try to automatically parse the URL as good as possible.
	 * <ul>
	 *		<li>Arrays are used as _GET</li>
	 * 	<li>App objects are used to identify the namespace</li>
	 *		<li>Strings that contain / or ? will be parsed and added to GET and path</li>
	 *		<li>The rest of strings will be pushed to the path.</li>
	 * </ul>
	 */
	public function __construct() {
		
		#Get the parameters the first time
		$sf     = spitfire();
		$params = func_get_args();
		
		#Extract the app
		if (reset($params) instanceof spitfire\App || $sf->appExists(reset($params))) {
			$app = array_shift($params);
		}
		else {
			$app = $sf;
		}
		
		#Get the controller, and the action
		$controller = null;
		$action     = null;
		$object     = Array();
		
		#Get the object
		while(!empty($params) && !is_array(reset($params)) ) {
			if     (!$controller) { $controller = array_shift($params); }
			elseif (!$action)     { $action     = array_shift($params); }
			else                  { $object[]   = array_shift($params); }
		}
		
		$this->params = array_shift($params);
		$environment  = array_shift($params);
		$this->path   = new \spitfire\core\Path($app, $controller, $action, $object, 'php', $environment);
	}
	
	public function setExtension($extension) {
		$this->extension = $extension;
		return $this;
	}
	
	public function getExtension() {
		return $this->extension;
	}
	
	public function getPath() {
		return array_filter($this->path);
	}
	
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}
	
	public function setApp($app) {
		$this->app = $app;
		return $this;
	}
	
	public function getApp() {
		return $this->app;
	}
	
	/**
	 * Sets a parameter for the URL's GET
	 * @param string $param
	 * @param string $value
	 * 
	 * [NOTICE] This function accepts parameters like controller,
	 * action or object that are part of the specification of nlive's 
	 * core. It is highly recommended not to use this "reserved words" 
	 * as parameters as they may cause the real values of these to be
	 * overwritten when the browser requests the site linked by these.
	 *
	 * @return self
	 */
	public function setParam($param, $value) {
		$this->params[$param] = $value;
		return $this;
	}
	
	public function setParams($values) {
		$this->params = $values;
		return $this;
	}
	
	/**
	 * Returns the value of a parameter set in the current URL.
	 * 
	 * @param string $parameter
	 * @return mixed
	 */
	public function getParameter($parameter) {
		if (isset($this->params[$parameter])) {
			return $this->params[$parameter];
		} else {
			return null;
		}
	}
	
	public function appendParameter($param, $value) {
		if ( isset($this->params[$param]) ) {
			if ( is_array($this->params[$param]) ){
				$this->params[$param][] = $value;
			} else {
				$this->params[$param] = Array($this->params[$param], $value);
			}
		}
		else {
			$this->params[$param] = Array($value);
		}
	}
	
	/**
	 * Serializes the URL. This method ill check if a custom serializer was defined
	 * and will then use the appropriate serializer OR fall back to the default 
	 * one.
	 * 
	 * @see URL::defaultSerializer() For the standard behavior.
	 */
	public function __toString() {
		$routes = $this->getRoutes();
		$url    = false;
		
		while($url === false && !empty($routes)) {
			/*@var $route spitfire\core\router\Route*/
			$route = array_shift($routes);
			
			$rev = $route->getReverser();
			if ($rev === null) { continue; }
			
			$url = $rev->reverse(
				$this->path->getApp(), 
				$this->path->getController(), 
				$this->path->getAction(), 
				$this->path->getObject(), 
				$this->path->getParameters());
		}
		
		#If the extension provided is special, we print it
		if ($this->path->getFormat() !== 'php') { $url.= ".{$this->path->getFormat()}"; }
		else                                    { $url.= '/'; }
		
		if ($this->params instanceof Get) {
			$url.= '?' . http_build_query($this->params->getRaw());
		}
		elseif (!empty($this->params)) {
			$url.= '?' . http_build_query($this->params);
		}
		
		return '/' . implode('/', array_filter([trim(SpitFire::baseUrl(), '/'), ltrim($url, '/')]));
	}

	/**
	 * @param string   $asset_name
	 * @param SpitFire $app
	 *
	 * @return string
	 */
	public static function asset($asset_name, $app = null) {
		#If there is no app defined we can use the default directory
		#Otherwise use the App specific directory
		$fpath = (!isset($app) ? ASSET_DIRECTORY : '/assets/').$asset_name;
		$modifiedAt = filemtime($fpath);
		$fpath .= "?$modifiedAt";
		return SpitFire::baseUrl() . '/' . $fpath;
	}
	
	public static function make($url) {
		return SpitFire::baseUrl() . $url;
	}
	
	public static function current() {
		$path = get_path_info();
		$refl = new ReflectionClass('URL');
		return $refl->newInstanceArgs(array_values(array_merge(explode('/', $path), Array($_GET))));
	}
	
	public static function canonical() {
		$ctx = current_context();
		$r   = Request::get();
		$canonical = new self();
		
		if (!$ctx) { throw new PrivateException("No context for URL generation"); }
		
		$default_controller = environment::get('default_controller');
		$default_action     = environment::get('default_action');
		
		$path = $ctx->app->getControllerURI($ctx->controller);
		if (count($path) == 1 && reset($path) == $default_controller) {
			$path = Array();
		}
		
		$action = $ctx->action;
		if ($action != $default_action) {
			$path[] = $action;
		}
		
		$canonical->setParams($_GET->getCanonical());
		
		#Add the object to the Path we generated so far and set it as Path
		$canonical->setPath(array_merge($path, $ctx->object));
		$canonical->setExtension($r->getPath()->getFormat());
		
		return $canonical;
	}
	
	public function toAbsolute() {
		$t = new AbsoluteURL();
		
		$t->setApp($this->app);
		$t->setExtension($this->extension);
		$t->setParams($this->params);
		$t->setPath($this->path);
		
		return $t;
	}
	
	public function getRoutes() {
		$router = \spitfire\core\router\Router::getInstance();
		return array_merge($router->server()->getRoutes(), $router->getRoutes());
	}

	public function offsetExists($offset) {
		if (is_numeric($offset)) { return isset($this->path[$offset]); }
		else                     { return isset($this->params[$offset]); }
	}

	public function offsetGet($offset) {
		if (is_numeric($offset)) { return $this->path[$offset]; }
		else                     { return $this->params[$offset]; }
	}

	public function offsetSet($offset, $value) {
		if (is_numeric($offset)) { return $this->path[$offset] = $value; }
		else                     { return $this->params[$offset] = $value; }
	}

	public function offsetUnset($offset) {
		if (is_numeric($offset)) { unset($this->path[$offset]); }
		else                     { unset( $this->params[$offset]); }
	}
}
