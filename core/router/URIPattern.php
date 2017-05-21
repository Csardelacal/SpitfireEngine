<?php namespace spitfire\core\router;

use Strings;

/* 
 * The MIT License
 *
 * Copyright 2017 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * The URIPattern class is a wrapper to gather several patterns and test them
 * simultaneously.
 */
class URIPattern
{
	/**
	 *
	 * @var Pattern[]
	 */
	private $patterns;
	
	private $open;
	
	public function __construct($pattern) {
		/*
		 * If the pattern ends in a slash it's not considered open ended, this is
		 * important for how we parse the pattern.
		 */
		$this->open     = !Strings::endsWith($pattern, '/');
		
		/*
		 * The patterns allow the system to test the URL piece by piece, making it
		 * more granular.
		 */
		$this->patterns = array_map(function ($e) { return new Pattern($e); }, array_filter(explode('/', $pattern)));
	}
	
	public function test($uri) {
		$pieces = array_filter(explode('/', $uri));
		$params = new Parameters();
		
		/*
		 * Extract the extension. To do so, we check whether the last element on 
		 * the URI has a dot in it, extract the extension and push the element 
		 * back onto the array.
		 */
		$last = explode('.', array_pop($pieces));
		$params->setExtension(isset($last[1])? array_pop($last) : 'php');
		array_push($pieces, implode('.', $last));
		
		/*
		 * Walk the patterns and test whether they're all satisfied. Remember that
		 * test raises an Exception when unsatisfied, so there's no need to check -
		 * if the code runs the route was satisfied
		 */
		for ($i = 0; $i < count($this->patterns); $i++) {
			$params->addParameters($this->patterns[$i]->test(array_shift($pieces)));
		}
		
		if (count($pieces)) {
			if ($this->open) { $params->setUnparsed($pieces); }
			else             { throw new RouteMismatchException('Too many parameters', 1705201331); }
		}
		
		return $params;
	}
	
}
