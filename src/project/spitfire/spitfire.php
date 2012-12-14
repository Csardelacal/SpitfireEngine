<?php

/**
 * Dispatcher class of Spitfire. Calls all the required classes for Spitfire to run.
 * @author César de la Cal <cesar@magic3w.com>
 * @package Spitfire
 */

class SpitFire 
{
	
	static $started         = false;

	static $autoload        = false;
	static $controller      = false;
	static $view            = false;
	static $model           = false;

	static $controller_name = false;
	static $action          = false;
	static $object          = false;
	static $extension       = false;
	
	/** var URL Depicts the current system url*/
	static $current_url     = false;

	static $debug           = false;

	public static function init() {

		if (self::$started) return false;
		$cur_dir = dirname(__FILE__);

		#Try to start autoload
		if (! class_exists('_SF_AutoLoad')) include dirname(__FILE__).'/autoload.php';
		self::$autoload = new _SF_AutoLoad();

		#Include file to define the location of core components
		self::includeIfPossible("$cur_dir/autoload_core_files.php");

		#Initialize the exception handler
		self::$debug = new _SF_ExceptionHandler();

		#Try to include the user's evironment & routes
		self::includeIfPossible(CONFIG_DIRECTORY . 'environments.php');
		self::includeIfPossible(CONFIG_DIRECTORY . 'routes.php');
		self::includeIfPossible(CONFIG_DIRECTORY . 'components.php');

		#Get the current path...
		self::$current_url = _SF_Path::getPath();

		self::$started = true;
		return true;
	}

	public static function fire() {

		#Import and instance the controller
		$_controller = implode('_', self::$current_url->getController()).'Controller';
		if (!class_exists($_controller)) throw new publicException("Page not found", 404);
		self::$controller = $controller = new $_controller();
		#Create the view
		self::$view = new View();
		#Create the model
		self::$model = new DBInterface();
		#Check if the action is available
		$method = Array($controller, self::$current_url->getAction());
		
		#Onload
		if (method_exists($controller, 'onload') ) 
			call_user_func_array(Array($controller, 'onload'), Array(self::$current_url->getAction()));
		#Fire!
		if (is_callable($method)) call_user_func_array($method, self::$current_url->getObject());
		else throw new publicException('E_PAGE_NOT_FOUND', 404);

		self::$view->render();
	}
	
	public static function baseUrl(){
		if (environment::get('base_url')) return environment::get('base_url');
		list($base_url) = explode('/index.php', $_SERVER['PHP_SELF'], 2);
		return $base_url;
	}

	public static function includeIfPossible($file) {
		if (file_exists($file)) return include $file;
		else return false;
	}

}