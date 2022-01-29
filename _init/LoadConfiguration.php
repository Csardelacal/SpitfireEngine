<?php namespace spitfire\_init;

use spitfire\core\config\Configuration;
use spitfire\core\Environment;

/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

class LoadConfiguration implements InitScriptInterface
{
	
	public function exec(): void 
	{
		
		/**
		 * If the cache file is available, we can use it to bootstrap the application.
		 */
		if (file_exists(spitfire()->locations()->root('bin/config.php'))) {
			spitfire()->config(new Configuration(include spitfire()->locations()->root('bin/config.php')));
			return;
		}
		
		$config = new Configuration();
		spitfire()->provider()->set(Environment::class, new Environment(spitfire()->locations()->root('.env')));
		
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
				$config->import($namespace . basename($file), include($file));
			}
			
			/*
			 * We iterate into folders to locate deeper seated models.
			 */
			$folders = glob($dir . '*', GLOB_ONLYDIR);
			
			foreach ($folders as $folder) {
				$walk($dir . basename($folder) . DIRECTORY_SEPARATOR, $namespace . basename($folder) . '.');
			}
		};
		
		$walk(spitfire()->locations()->config(), '');
		spitfire()->provider()->set(Configuration::class, $config);
	}
}
