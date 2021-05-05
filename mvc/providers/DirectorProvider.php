<?php namespace spitfire\mvc\providers;

use spitfire\service\Provider;
use spitfire\core\kernel\ConsoleKernel;

/* 
 * The MIT License
 *
 * Copyright 2020 César de la Cal Bretschneider <cesar@magic3w.com>.
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

/**
 * The director provider class registers the commands that spitfire provides to
 * applications using it.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class DirectorProvider extends Provider
{
	
	/**
	 * 
	 */
	public function register()
	{
		/*
		 * The director provider is only loaded in order to register the known 
		 * spitfire provided services.
		 */
	}
	
	
	public function init()
	{
		
		$kernel = spitfire()->kernel();
		
		/*
		 * We only need to register the directors if our kernel is actually the 
		 * console kernel. We cannot work with directors on the web server.
		 */
		if ($kernel instanceof ConsoleKernel) {
			$kernel->register('spitfire.config.build', new \spitfire\config\directors\BuildConfigDirector());
			$kernel->register('spitfire.app.cache.build', new \spitfire\app\directors\BuildCacheDirector());
			$kernel->register('spitfire.defer.process', \spitfire\defer\directors\ProcessDirector::class);
		}
	}
}
