<?php namespace spitfire\core\http;

use ReflectionClass;
use spitfire\core\Path;
use spitfire\core\router\Route;
use spitfire\core\router\Router;
use spitfire\exceptions\PrivateException;
use spitfire\io\Get;
use spitfire\SpitFire;

/**
 * 
 * This dinamically generates system urls this allows us to validate URLs if needed
 * or generate different types of them depending on if pretty links is enabled
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class URL
{
	/**
	 * @var Path Contains information about the controller / action
	 * / object combination that will be used for this URL.
	 */
	private $path;
	
	/**
	 * @var mixed|Get[] Contains data about the _GET parameters this URL will pass
	 * to the system if invoked by the user.
	 */
	private $params = Array();

	
	public function __construct($app, $controller = null, $action = null, $object = null, $extension = null, $get = null, $environment = null) {
		
		$this->params = $get;
		$this->path   = new Path($app, $controller, $action, $object, $extension, $environment);
	}
	
	public function setExtension($extension) {
		$this->path->setFormat($extension);
		return $this;
	}
	
	public function getExtension() {
		return $this->path->getFormat();
	}
	
	public function getPath() {
		return $this->path;
	}
	
	public function setPath($path) {
		$this->path = $path;
		return $this;
	}
	
	public function setApp($app) {
		$this->path->setApp($app);
		return $this;
	}
	
	public function getApp() {
		return $this->path->getApp();
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
			/*@var $route Route*/
			$route = array_shift($routes);
			
			$rev = $route->getReverser();
			if ($rev === null) { continue; }
			
			$url = $rev->reverse($this->path);
		}
		
		foreach ($this->getRedirections() as $red) {
			try { $url = $red->reverse($url); }
			catch (\Exception$e) { /*Ignore*/ }
		}
		
		#If the extension provided is special, we print it
		if ($this->path->getFormat() !== 'php') { $url.= ".{$this->path->getFormat()}"; }
		else                                    { $url = rtrim($url, '/') . '/'; }
		
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
		$path = getPathInfo();
		$refl = new ReflectionClass(URL::class);
		return $refl->newInstanceArgs(array_values(array_merge(explode('/', $path), Array($_GET))));
	}
	
	public static function canonical() {
		$ctx = current_context();
		$canonical = new self($ctx->app);
		$path = $canonical->getPath();
		
		if (!$ctx) { 
			throw new PrivateException("No context for URL generation"); 
		}
		
		$path->setController($ctx->app->getControllerURI($ctx->controller));
		$path->setAction($ctx->action);
		$path->setObject($ctx->object);
		
		$canonical->setParams($_GET->getCanonical());
		
		#Add the object to the Path we generated so far and set it as Path
		$canonical->setExtension($ctx->request->getPath()->getFormat());
		
		return $canonical;
	}
	
	public function absolute($domain = null) {
		$t = new AbsoluteURL($this->getApp());
		
		$t->setExtension($this->getExtension());
		$t->setParams($this->params);
		$t->setPath($this->path);
		
		return $t->setDomain($domain);
	}
	
	public function getRoutes() {
		$router = Router::getInstance();
		return array_merge(
			$router->server()->getRoutes()->toArray(), $router->getRoutes()->toArray()
		);
	}
	
	/**
	 * 
	 * @return \spitfire\core\router\Redirection[]
	 */
	public function getRedirections() {
		$router = Router::getInstance();
		return array_merge(
			$router->server()->getRedirections()->toArray(), $router->getRedirections()->toArray()
		);
	}

}
