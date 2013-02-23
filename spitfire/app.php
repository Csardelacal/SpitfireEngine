<?php

use spitfire\View;
use spitfire\SpitFire;

/**
 * Spitfire Application Class. This class is the base of every other 'app', an 
 * app is a wrapper of controllers (this allows to plug them into other SF sites)
 * that defines a set of rules to avoid collissions with the rest of the apps.
 * 
 * Every app resides inside of a namespace, this externally defined variable
 * defines what calls Spitfire redirects to the app.
 */
abstract class App
{
	public $view;
	public $controller;
	public $basedir;
	public $namespace;
	
	/**
	 * Creates a new App. Receives the directory where this app resides in
	 * and the URI namespace it uses.
	 * 
	 * @param String $basedir The root directory of this app
	 * @param String $namespace The URI namespace it 'owns'
	 */
	public function __construct($basedir, $namespace) {
		$this->basedir = $basedir;
		$this->namespace = $namespace;
	}
	
	public function runTask($controller, $action, $object) {
		#Create a controller
		$controllerClass = $this->getControllerClassName($controller);
		if (!class_exists($controllerClass)) throw new publicException('Page not found', 404);
		$this->controller = new $controllerClass($this);
		
		#Create a view
		$viewClass = $this->getViewClassName($controller);
		if (class_exists($viewClass)) $this->view = new $viewClass($this);
		else $this->view = new View($this);
		
		#Run the onload
		if (method_exists($this->controller, 'onload') ) 
			call_user_func_array(Array($this->controller, 'onload'), Array($action));
		
		#Check if the controller can handle the request
		$request = Array($this->controller, $action);
		if (is_callable($request)) call_user_func_array($request, $object);
		else throw new publicException('Action not found', 404);
	}
	
	
	public function url($url) {
		
		$ns = ($this->namespace)? '/' . $this->namespace : '';
		
		if (0 === strpos($url, 'http://')) return $url;
		if (0 === strpos($url, 'https://')) return $url;
		if (0 === strpos($url, 'www.')) return 'http://' . $url;
		else return SpitFire::baseUrl() . $ns . $url;
	}
	
	public function getBaseDir() {
		return $this->basedir;
	}
	
	abstract public function enable();
	abstract public function getAssetsDirectory();
	abstract public function getTemplateDirectory();
	abstract public function hasController($controller);
	abstract public function getControllerClassName($controller);
	abstract public function getViewClassName($controller);
	
}