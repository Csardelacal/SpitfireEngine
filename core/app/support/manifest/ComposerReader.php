<?php namespace spitfire\core\app\support\manifest;

use BadMethodCallException;
use spitfire\collection\Collection;
use spitfire\core\app\AppManifest;

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

/**
 * This class extracts manifest data from composer.json and allows the application
 * to assemble an application booststrap object that it can then cache and use to
 * get the application running.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class ComposerReader
{
	
	/**
	 * Reads the contents of a composer.json file and returns the manifest data
	 * that it contains.
	 * 
	 * @param string $file
	 */
	public static function read($file) 
	{
		/*
		 * Read the contents of the composer file for this package.
		 */
		$json = json_decode(file_get_contents($file), null, 512, JSON_THROW_ON_ERROR);
		
		/*
		 * Some composer files may not contain an extra.spitfire section. If this is
		 * the case we probably imported a package that contains no Spitfire application
		 * and we must stop execution.
		 */
		if ($json->extra && $json->extra->spitfire) {
			/**
			 * The process of reading the apps into the system is recursive. Please note that
			 * the dependency tree terminates the recursion. The application will not be able
			 * to work with circular dependencies.
			 */
			$apps = (new Collection((array)($json->extra->spitfire->apps?? [])))
				->each(function ($app) { $this->read(spitfire()->locations()->root('vendor/' . $app . '/composer.json')); });
			
			/**
			 * Extract the events that this application is listening for
			 * 
			 * @todo Determine the exact syntax of the events key
			 */
			$events = (array)($json->extra->spitfire->events?? []);
		}
		else {
			throw new BadMethodCallException(sprintf('The %s manifest is not a valid spitfire manifest', $file));
		}
		
		#TODO: Check the data in extra.spitfire is correctly formatted
		
		return new AppManifest($json->name?? '', $apps, $events);
	}
	
	/**
	 * 
	 * @todo Allow passing a list of packages to be ignored by this function
	 * @param string $installedJSON
	 * @return string[]
	 */
	public static function providers(string $installedJSON) : array
	{
		
		/*
		* We need to read the installed.json file that composer generates, which includes
		* all the packages that our application depends on.
		*/
		$json = json_decode(file_get_contents($installedJSON), null, 512, JSON_THROW_ON_ERROR);
		$packages = $json->packages;
		
		/**
		 * Prepare an empty array that we will use to populate the service providers that our 
		 * application needs to load.
		 */
		$providers = [];
		
		foreach ($packages as $package) {
			/**
			 * The service providers are written to the `extra` key inside a package's spec, if
			 * the extra key is missing, the application is not registering any.
			 */
			if (!isset($package->extra)) { continue; }
			if (!isset($package->extra->spitfire)) { continue; }
			if (!isset($package->extra->spitfire->providers)) { continue; }
			
			/**
			 * Ensure that the data we're receiving is an array, otherwise we're guaranteed to
			 * not have a working dataset.
			 */
			assert(is_array($package->extra->spitfire->providers));
			
			$providers = array_merge($providers, $package->extra->spitfire->providers);
		}
		
		return $providers;
	}
}
