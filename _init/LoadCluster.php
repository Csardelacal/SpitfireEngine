<?php namespace spitfire\_init;

use spitfire\App;
use spitfire\core\app\AppManifest;
use spitfire\core\app\Cluster;
use spitfire\core\app\support\manifest\ComposerReader;
use spitfire\core\router\Router;

/* 
 * The MIT License
 *
 * Copyright 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
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
			$reader = new ComposerReader();
			$manifest = $reader->read(spitfire()->locations()->root('composer.json'));
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
