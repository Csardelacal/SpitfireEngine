<?php namespace spitfire\core\http;

use spitfire\core\Path;
use spitfire\core\router\Route;
use spitfire\core\router\Router;
use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\PrivateException;
use spitfire\io\Get;
use spitfire\SpitFire;

/**
 * 
 * This dynamically generates system urls this allows us to validate URLs if needed
 * or generate different types of them depending on if pretty links is enabled
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class URL implements \JsonSerializable
{
	
	/**
	 * @var bool Indicates whether the URL should be prefixed with the application's
	 * hostname. This hostname will be retrieved from the application configuration
	 * if available (otherwise the system will attempt to guess it or throw an exception)
	 */
	private $absolute = false;
	
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

	
	public function __construct($controller = null, $action = null, $object = null, $extension = null, $get = null, $environment = null) {
		
		$this->params = $get;
		$this->path   = new Path($controller, $action, $object, $extension, $environment);
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
	public function stringify() {
		$routes = $this->getRoutes();
		$url    = false;
		
		while($url === false && !empty($routes)) {
			/*@var $route Route*/
			$route = array_shift($routes);
			
			$rev = $route->getReverser();
			if ($rev === null) { continue; }
			
			$url = $rev->reverse($this->path);
		}
		
		if (empty(trim($url, '/')) && $this->path->getFormat() !== 'php') {
			$url = $rev->reverse($this->path, true);
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
		
		/**
		 * We default to enforcing HTTPS on absolute URLs. This reduces complexity on our APIs
		 * and there is currently no environment where we do provide HTTP access to our services.
		 */
		if ($this->absolute) {
			return 'https://' . $this->hostname() . '/' . ltrim(implode('/', [trim(SpitFire::baseUrl(), '/'), ltrim($url, '/')]), '/');
		}
		else {
			return '/' . ltrim(implode('/', [trim(SpitFire::baseUrl(), '/'), ltrim($url, '/')]), '/');
		}
	}
	
	public function __toString()
	{
		return $this->stringify();
	}
	
	public static function make($url) {
		return SpitFire::baseUrl() . $url;
	}
	
	public static function current() {
		$ctx = current_context();
		
		if (!$ctx) { 
			throw new PrivateException("No context for URL generation"); 
		}
		
		$object = array_map(function($e) {
			return $e instanceof \spitfire\Model? implode(':', $e->getPrimaryData()) : $e;
		}, $ctx->object);
		
		return new URL($ctx->app->getControllerLocator()->getControllerURI($ctx->controller), $ctx->action, $object, $ctx->extension, $_GET);
	}
	
	public static function canonical() {
		$ctx = current_context();
		
		if (!$ctx) { 
			throw new PrivateException("No context for URL generation"); 
		}
		
		return new URL($ctx->controller, $ctx->action, $ctx->object, $ctx->extension, $_GET->getCanonical());
	}
	
	public function absolute(bool $set = true) : URL
	{
		$this->absolute = $set;
		return $this;
	}
	
	public function getRoutes() {
		$router = spitfire()->provider()->get(Router::class);
		
		return array_filter(array_merge(
			$router->getRoutes()->toArray(), $router->getRoutes()->toArray()
		));
	}
	
	public function hostname() : string
	{
		/**
		 * If this url is not set to be an absolute url, the hostname is left
		 * empty.
		 */
		if (!$this->absolute) {
			return '';
		}
		
		/**
		 * If the application has defined a hostname for itself, the URL generator
		 * should respect this and use the URL provided.
		 */
		if (config('app.hostname', false)) {
			return config('app.hostname');
		}
		
		/**
		 * If the application provides no hostname for itself, the web-server's hostname
		 * will be used. Please note that there's a few security considerations when 
		 * working with this, so it's recommended to set a canonical hostname in config.
		 */
		if ($_SERVER['SERVER_NAME']?? false) {
			return $_SERVER['SERVER_NAME'];
		}
		
		throw new ApplicationException('Could not detemine the hostname for an absolute url', 2104181221);
	}
	

	/**
	 * Returns the string representation of the URL. This is because the URL object 
	 * does contain additional data that will generally cause unexpected behavior
	 * when rendering a URL.
	 * 
	 * One would not expect that json_encode(['url' => new URL()]) would lead to 
	 * a complex object, but instead to {url: '/'}
	 * 
	 * @return string
	 */
	public function jsonSerialize() 
	{
		return (string)$this;
	}
	
}
