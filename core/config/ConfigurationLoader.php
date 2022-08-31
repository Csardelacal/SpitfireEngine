<?php namespace spitfire\core\config;

use spitfire\core\Locations;
use Dotenv\Dotenv;
use spitfire\contracts\core\LocationsInterface;

class ConfigurationLoader
{
	
	private LocationsInterface $locations;
	
	public function __construct(Locations $locations)
	{
		$this->locations = $locations;
	}
	
	public function make(): Configuration
	{
		
		$config = new Configuration();
		(Dotenv::createImmutable($this->locations->root()))->load();
		
		/*
		 * This function walks the directory for the config and loads the appropriate
		 * data.
		 */
		$walk = function ($dir, $namespace) use (&$walk, $config) {
			/*
			 * We're only interested in PHP files, since these contain the models.
			 * The system does not just import all the models and use reflection to
			 * locate them, instead, it does some magic with the filenames.
			 *
			 * There's a certain level of risk we assume whenever blindly looping
			 * over a set of files in PHP and including them. But other than manually
			 * parsing them - there's not much we can do to look for class declarations
			 * in them. The models folder should be for models only.
			 */
			$scripts = glob($dir . '*.php');
			
			foreach ($scripts as $file) {
				$config->import($namespace . basename($file, '.php'), include($file));
			}
			
			/*
			 * We iterate into folders to locate deeper seated models.
			 */
			$folders = glob($dir . '*', GLOB_ONLYDIR);
			
			foreach ($folders as $folder) {
				$walk($dir . basename($folder) . DIRECTORY_SEPARATOR, $namespace . basename($folder) . '.');
			}
		};
		
		$walk($this->locations->config(), '');
		return $config;
	}
}
