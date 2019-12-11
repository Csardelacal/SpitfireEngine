<?php namespace spitfire\core\event;

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

class RecipeLoader
{
	
	private static $loaded = false;
	
	/**
	 * Loads all the recipes that the system needs to ensure that the events get 
	 * dispatched. Events get only loaded whenever the event() function is actually
	 * called, which should make the footprint of the event system negligible when
	 * it is not being used.
	 * 
	 * Core components must not invoke the event() function or call this method.
	 * 
	 * @todo This should implement a caching mechanism so the directory does not need to
	 *       be scanned with every request that uses it
	 * @return void
	 */
	public static function import() {
		/*
		 * Obviously, if the listeners have been appropriately loaded, we can stop
		 * them from being imported again.
		 */
		if (self::$loaded) { return; }
		
		/*
		 * Find all the PHP files in the recipes folder. These are loaded to allow
		 * them to register with the application.
		 */
		$recipes = glob(basedir() . '/bin/recipes/*.php');
		
		/*
		 * Loop over the recipes, and include them all.
		 */
		foreach ($recipes as $recipe) {
			include $recipe;
		}
		
		self::$loaded = true;
	}
	
}
