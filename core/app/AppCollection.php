<?php namespace spitfire\core\app;

use spitfire\App;
use spitfire\collection\Collection;
use spitfire\utils\Strings;

/*
 * The MIT License
 *
 * Copyright 2019 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * The app collection is intended to provide spitfire with a good mechanism
 * to look up applications by either class path or url.
 *
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class AppCollection extends Collection
{
	
	/**
	 * Returns the application attached to the path in question.
	 *
	 * @param string $url
	 * @return App|null
	 * @todo Introduce AppNotFound exception
	 */
	public function byUrl(string $url)
	{
		
		/*
		 * URL namespaces must be delimited by slashes. This means that
		 * paths are only valid if they start and end with a slash. This
		 * prevents the system from performing fuzzy matching.
		 *
		 * This rule also applies to "/", which starts and ends with a
		 * single slash.
		 */
		if (!Strings::startsWith($url, '/')) {
			$url = '/' . $url;
		}
		if (!Strings::endsWith($url, '/')) {
			$url.= '/';
		}
		
		/**
		 * Search the list of applications for one that matches the given path.
		 */
		return $this->filter(function ($e) use ($url) {
			return $e->url() == $url;
		})->first();
	}
	
	/**
	 * When passing a class name to this function, the system must return a class that
	 * this correlates with.
	 *
	 * @param string|object $name
	 * @return App|null
	 * @todo Introduce AppNotFound exception
	 * @todo Deal with special class types like controllers and directors
	 */
	public function byClassName($name):? App
	{
		
		if (!is_string($name)) {
			$name = get_class($name);
		}
		
		/*@var $app App*/
		foreach ($this as $app) {
			if (Strings::startsWith($name, $app->namespace())) {
				return $app;
			}
		}
		
		return null;
	}
}
