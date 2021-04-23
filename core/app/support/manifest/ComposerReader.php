<?php namespace spitfire\core\app\support\manifest;

use spitfire\collection\Collection;
use spitfire\core\app\AppManifest;

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
}
