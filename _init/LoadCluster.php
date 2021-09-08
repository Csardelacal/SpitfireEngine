<?php namespace spitfire\_init;

use spitfire\core\app\AppManifest;
use spitfire\core\app\support\manifest\ComposerReader;
use spitfire\core\router\Router;

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

class LoadCluster implements InitScriptInterface
{
	
	public function exec(): void 
	{
		
		$cache = spitfire()->locations()->root('bin/apps.php');
		
		if (file_exists($cache)) {
			$manifest = include $cache;
		}
		else {
			$manifest = ComposerReader::read(spitfire()->locations()->root('composer.json'));
		}
		
		/**
		 * Get the cluster from spitfire() so we can start putting apps into it.
		 */
		$cluster = spitfire()->cluster();
		$router  = spitfire()->provider()->get(Router::class);
		
		/**
		 * Create the root application. This may as well be an app in a imported package, so you
		 * can create a `metaapplication` that just combines another app with several plugins to 
		 * build an app that you can commit to a repository, build, etc.
		 */
		$app = (new $manifest->getEntryPoint())($router);
		$cluster->put($app);
		
		/**
		 * @todo Spitfire should load the providers list from a method within the app, this allows us
		 * to define the providers wherever we want (like configuration or something)
		 */
		
		/**
		 * At this point, it's really not interesting to make this recurse deeper into other
		 * applications. This would just make the concept harder to understand, and applications
		 * harder to control without providing much use to us.
		 */
		foreach ($manifest->getApps() as $url => $extension) {
			/**
			 * Make sure that nothing but app manifests get loaded here.
			 */
			assert($extension instanceof AppManifest);
			
			/**
			 * Initialize the subordinated application within it's url space. This allows the application
			 * to register routes being aware of the namespace.
			 */
			$_e = (new $manifest->getEntryPoint())($router->scope($url));
			$cluster->put($_e);
			
			
			/**
			 * @todo Spitfire should load the providers list from a method within the app, this allows us
		 	 * to define the providers wherever we want (like configuration or something)
			 */
		}
		
	}
}
