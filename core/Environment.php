<?php namespace spitfire\core;

use BadMethodCallException;

/**
 * The environment loads settings from an ini file, and from the system's environment,
 * making them accessible to the application.
 * 
 * If you use config caching you should not use this outside of the config files, since
 * the data in the environment gets 'baked' into the config and cached to a file.
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
	protected $settings = [];
	
	/**
	 * Loads the environment file from disk and initializes an environment for the application,
	 * please note that the envioronment file is always loaded from a location relative to the
	 * application root.
	 * 
	 * Using an environment file outside the application root is usually not advisable, and may
	 * lead to unexpected behavior.
	 */
	public function __construct($env = '.env') 
	{
		$file = spitfire()->locations()->root($env);
		
		/**
		 * Load the ini file that represents the environment on this machine.
		 */
		if (file_exists($env)) {
			$this->settings = parse_ini_file($file, false, INI_SCANNER_TYPED);
		}
		#TODO: Add a log output error whenever the environment failed to load
	}
	
	/**
	 * This function creates / overrides a setting with a value defined by the
	 * developer. Names are case insensitive.
	 * 
	 * @deprecated since v0.2 ()
	 * @param string $key The name of the setting
	 * @param string $value The value of the parameter.
	 */
	public function set($key, $value)
	{
		$low = strtolower($key);
		$this->settings[$low] = $value;
	}
	
	/**
	 * Returns the selected key from the settings.
	 * 
	 * @param string $key The key to be returned.
	 * @return mixed
	 */
	public function read($key)
	{
		if (isset($this->settings[$key])) {
			return $this->settings[$key]; 
		}
		else {
			return getenv($key); 
		}
	}
	
	/**
	 * This method has been deprecated, the system should not use the subtree method to
	 * explore system configuration, instead, it should use service providers for this
	 * and refer to the config only with known keys.
	 * 
	 * @deprecated since 0.2-dev
	 * @see https://phabricator.magic3w.com/T69 for further notes
	 */
	public function subtree($namespace)
	{
		$_return = [];
		
		foreach ($this->settings as $key => $entry) {
			# Obviously, if the key is not part of the namespace we just skip it.
			if (!\spitfire\utils\Strings::startsWith($key, $namespace)) {
				continue; 
			}
			
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
	 * @deprecated since v0.2
	 * @param string $key The key to be returned.
	 * @return string|Environment
	 */
	public static function get($key = null)
	{
		throw new BadMethodCallException('Environment::get is deprecated');
	}
}
