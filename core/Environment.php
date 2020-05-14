<?php namespace spitfire\core;

/**
 * Environments are a way to store multiple settings for a single application
 * and several machines. We can even set automatic environment detection to 
 * make the process of switching the settings for several servers seamless.
 * 
 * @author  César de la Cal <cesar@magic3w.com>
 */

class Environment
{
	/**
	 * Default settings. This array contains the settings that are predefined
	 * in Spitfire. They can later be overriden if needed by using set inside 
	 * of each environment.
	 */
	protected $settings = Array (
		#Maintenance related settings.
		'maintenance_enabled'      => false,
		'maintenance_controller'   => 'maintenance',
		'debug_mode'           => true, //TODO: Change for stable
		
		#Database settings
		'db'                       => 'mysqlpdo://root:@localhost:3306/database',
	
		#Character encoding settings
		'system_encoding'          => 'utf-8',
		'database_encoding'        => 'latin1',
	    
		#MVC Related settings
		'pretty_urls'              => true,
		'default_controller'       => 'home',
		'default_action'           => 'index',
		'default_object'           => Array(),
	    
		#Request settings
		'supported_view_extensions'=> Array('php', 'xml', 'json'),
		'request.replace_globals'  => true,
		
		#Memcached settings
		'memcached_enabled'        => false,
		'memcached_servers'        => Array('localhost'),
		'memcached_port'           => '11211',
		 
		#Directory settings
		'cachefile.directory'     => 'app://bin/usr/cache/',
		'uploads.directory'       => 'app://bin/usr/uploads/',
		'sessions.directory'      => 'app://bin/usr/sessions/',
	    
		#Asset preprocessors
		'assets.preprocessors.scss' => '\spitfire\io\asset\SassPreprocessor',
		'assets.preprocessors.js'   => '\spitfire\io\asset\JSPreprocessor',
		'assets.preprocessors.png'  => '\spitfire\io\asset\PNGPreprocessor',
		'assets.preprocessors.jpg'  => '\spitfire\io\asset\JPEGPreprocessor',
		'assets.preprocessors.jpeg' => '\spitfire\io\asset\JPEGPreprocessor',
	    
		#Timezone settings
		'timezone'                 => 'Europe/Berlin',
		'datetime.format'          => 'd/m/Y H:i:s',
		
		'storage.engines.app'      => ['\spitfire\storage\drive\Driver', '@'],
		'storage.engines.uploads'  => ['\spitfire\storage\drive\Driver', '@bin/usr/uploads']
		
	);
	
	/**
	 * The array of declared environments. This allows the user to easily define
	 * several configurations that they can manage with ease.
	 *
	 * @var Environment[]
	 */
	static    $envs               = Array();
	
	/**
	 * The environment currently being used by the system. This is only used by the
	 * singleton methods and should be avoided in multi-head and multi-context
	 * environments.
	 *
	 * @var Environment|null
	 */
	static    $active_environment = null;
	
	/**
	 * When creating a new environment it'll be created with a name that will
	 * identify it and a set of default settings that can be overriden afterwards.
	 * 
	 * @param string $env_name Name of the environment i.e. Testing.
	 */
	public function __construct($env_name) {
		self::$envs[$env_name] = $this;
		self::$active_environment = $this;
	}
	
	public function keys() {
		return array_keys($this->settings);
	}
	
	/**
	 * This function creates / overrides a setting with a value defined by the
	 * developer. Names are case insensitive.
	 * @param string $key The name of the setting
	 * @param string $value The value of the parameter.
	 */
	public function set ($key, $value) {
		$low = strtolower($key);
		$this->settings[$low] = $value;
	}
	
	/**
	 * Defines which environment should be used to read data from it.
	 * @param string|Environment $env The environment to be used.
	 */
	public static function set_active_environment ($env) {
		if (is_a($env, __class__) )                          { self::$active_environment = $env; }
		elseif (is_string($env) && isset(self::$envs[$env])) { self::$active_environment = self::$envs[$env]; }
	}
	
	/**
	 * Returns the selected key from the settings.
	 * @param string $key The key to be returned.
	 */
	public function read($key) {
		$low = strtolower($key);
		if (isset( $this->settings[$low] )) { return $this->settings[$low]; }
		else { return false; }
	}
	
	/**
	 * Returns a slice of the environment containing all the records that match
	 * the namespace given as a parameter.
	 * 
	 * For example, Environment::subtree('memcached.') will return all the records
	 * pertaining to memcached in an associative array.
	 * 
	 * This function has to loop over all the entries since, generally speaking,
	 * environments are not recursive. They just have a simple dictionary with 
	 * strings. No further arrays.
	 * 
	 * @see https://phabricator.magic3w.com/T69 for further notes
	 */
	public function subtree($namespace) {
		$_return = [];
		
		foreach ($this->settings as $key => $entry) {
			# Obviously, if the key is not part of the namespace we just skip it.
			if (!\Strings::startsWith($key, $namespace)) { continue; }
			
			# Otherwise we will trim the key so that only the non-obvious part of
			# it is left, and add it to the return.
			$_return[substr($key, strlen($namespace))] = $entry;
		}
		
		return $_return;
	}
	
	/**
	 * Static version of read. Will return the selected key from the currently 
	 * active environment.
	 * 
	 * @param string $key The key to be returned.
	 * @return string|Environment
	 */
	public static function get($key = null) {
		#If no key was set we're expecting the system to return the active environment
		if ($key === null) { return self::$active_environment? : new self('default');}
		
		if (self::$active_environment) { return self::$active_environment->read($key); }
		#Implicit else
		new self('default');
		return self::get($key); //Repeat
	}
	
}
