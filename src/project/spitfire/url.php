<?php

use spitfire\SpitFire;
use spitfire\Request;

/**
 * 
 * This dinamically generates system urls
 * this allows us to validate URLs if needed
 * or generate different types of them depending
 * on if pretty links is enabled
 * @author César de la Cal <cesar@magic3w.com>
 *
 */
class URL implements ArrayAccess
{
	/**
	 * @var App Contains a reference to the App that should be used to handle
	 * the request generated by this URL. Note that if for any reason the
	 * App's namespace changes this class will have trouble linking correctly.
	 */
	private $app;
	
	/**
	 * @var string[] Contains a list of the URL parameters that have no name
	 * (elements declared in the path - like /this/is/the/path).
	 */
	private $path = Array();
	
	/**
	 * @var mixed[] Contains data about the _GET parameters this URL will pass
	 * to the system if invoked by the user.
	 */
	private $params = Array();
	
	/**
	 * @var string Contains the 'extension' of the data being used by this
	 * URL. When called this will alter the template the view tries to load
	 * when generating a response. 
	 */
	private $extension = 'php';
	
	/**
	 * This static method allows your application to provide a custom serializer
	 * for your app. This is useful if you're using a custom routing system that 
	 * does not comply with the standard controller/action/object format.
	 *
	 * @var Closure
	 */
	private static $serializer = null;
	
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
	 * @param mixed $_ You can pass any amount of parameters to this class,
	 * the constructor will try to automatically parse the URL as good as possible.
	 * <ul>
	 *		<li>Arrays are used as _GET</li>
	 * 	<li>App objects are used to identify the namespace</li>
	 *		<li>Strings that contain / or ? will be parsed and added to GET and path</li>
	 *		<li>The rest of strings will be pushed to the path.</li>
	 * </ul>
	 */
	public function __construct() {
		$params = func_get_args();
		
		#Loop through the parameters checking for content.
		foreach ($params as $param) {
			#Check if the parameter is an array, if it is it's GET
			if (is_array($param) || $param instanceof Iterator) { $this->params = $param; }
			
			#If it's an App object, it means that it's got a special place in the Path
			elseif ($param instanceof App) { $this->app = $param; }
			
			#If we get a whole block of text with the raw url, parse it
			#To improve performance, we only do this IF the user has provided only that parameter
			elseif (!isset($params[1]) && ( strstr($param, '/') || strstr($param, '?') ) ) {
				$info = parse_url($param);
				$this->path = array_merge ($this->path, explode('/', $info['path']));
				$this->params = isset($info['query'])? parse_str($info['query']) : $this->params;
			}
			
			#Otherwise it's just a path component
			else  { $this->path[] = $param; }
		}
		
		#Check if the first element of the path is an app.
		if (!$this->app && isset($this->path[0]) && spitfire()->appExists($this->path[0])) {
			$this->app = spitfire()->getApp(array_shift($this->path));
		}
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
	 */
	public function setParam($param, $value) {
		$this->params[$param] = $value;
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
	 * __toString()
	 * This function generates a URL for any page that nLive handles,
	 * it's output depends on if pretty / rewritten urls are active.
	 * If they are it will return /controller/action/object?params
	 * based urls and in any other case it'll be a normal GET based
	 * url.
	 */
	public function __toString() {
		#In case of a custom serializer. We will need to respect that
		if (self::$serializer !== null) { 
			#In case the serializer rejects the URL we will use the standard serializer
			$_ret = self::$serializer($this); 
			if ($_ret) { return $_ret; }
		}
		
		$path = $this->path;
		if ($this->app) { array_unshift ($path, $this->app->getURISpace()); }
		
		#Create the URL full path (base URL + Request path)
		$str =  SpitFire::baseUrl() . '/' . implode('/', array_filter($path));
		
		#If the extension provided is special, we print it
		if ($this->extension !== 'php') { $str.= ".$this->extension"; }
		
		if (!empty($this->params)) {
			$str.= '?' . http_build_query($this->params);
		}
		
		return $str;
	}
	
	public static function asset($asset_name, $app = null) {
		if ($app == null) return SpitFire::baseUrl() . '/assets/' . $asset_name;
		else return SpitFire::baseUrl() . '/' . $app->getAssetsDirectory() . $asset_name;
	}
	
	public static function make($url) {
		return SpitFire::baseUrl() . $url;
	}
	
	public static function current() {
		$path = get_path_info();
		return new self($path, $_GET);
	}
	
	public static function canonical() {
		$ctx = current_context();
		$r   = Request::get();
		$canonical = new self($_GET);
		if (!$context) throw new privateException("No context for URL generation");
		
		$default_controller = environment::get('default_controller');
		$default_action     = environment::get('default_action');
		
		$path   = $ctx->app->getControllerURI();
		if (count($path) == 1 && reset($path) == $default_controller) {
			$path = Array();
		}
		
		$action = $ctx->action;
		if ($action != $default_action) {
			$path[] = $action;
		}
		
		$path = array_merge($path, $ctx->object);
		
		$canonical->setPath($path);
		$canonical->setExtension($r->getExtension());
		
		return $canonical;
	}

	public function offsetExists($offset) {
		if (is_numeric($offset)) return isset($this->path[$offset]);
		else return isset($this->params[$offset]);
	}

	public function offsetGet($offset) {
		if (is_numeric($offset)) return $this->path[$offset];
		else return $this->params[$offset];
	}

	public function offsetSet($offset, $value) {
		if (is_numeric($offset)) return $this->path[$offset] = $value;
		else return $this->params[$offset] = $value;
	}

	public function offsetUnset($offset) {
		if (is_numeric($offset)) unset($this->path[$offset]);
		else unset( $this->params[$offset]);
	}
}