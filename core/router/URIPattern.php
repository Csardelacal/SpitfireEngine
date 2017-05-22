<?php namespace spitfire\core\router;

use spitfire\exceptions\PrivateException;
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
	
	/**
	 * 
	 * @param type $parameters
	 * @return type
	 * @throws PrivateException
	 * @throws RouteMismatchException
	 */
	public function reverse($parameters) {
		
		/*
		 * If the data we're receiving is a parameters object, then we'll extract
		 * the raw data from it in order to work with it.
		 */
		$params = $parameters instanceof Parameters? $parameters->getParameters() : $parameters;
		$add    = $parameters instanceof Parameters? $parameters->getUnparsed() : [];
		
		/*
		 * If there is parameters that exceed the predefined length then we drop
		 * them if the route does not support them.
		 */
		if (!empty($add) && !$this->open) {
			throw new PrivateException('This route rejects additional params', 1705221031);
		}
		
		/*
		 * Prepare a pair of variables we need for the loop. First of all we need
		 * to ensure that we have an array to write the replacements to, and then
		 * we need a counter to make sure that all the parameters given were also
		 * used.
		 */
		$replaced = [];
		$left     = count($params);
		
		/*
		 * Loop over the patterns and test the parameters. There's one quirk - the
		 * system assumes that a parameter is never used twice but won't fail
		 * gracefully if the user ignores that restriction.
		 */
		foreach($this->patterns as $p) {
			
			#Static URL elements
			if(!$p->getName()) { 
				$replaced[] = $p->getPattern()[0]; 
				continue;
			}
			
			/*
			 * For non static URL elements we check whether the appropriate parameter
			 * is defined. Then we test it and if the parameter was defined we will
			 * accept it.
			 */
			$defined  = !isset($params[$p->getName()])? $params[$p->getName()] : null;
			$replaced = array_merge($replaced, $p->test($defined));
			$defined? $left-- : null;
		}
		
		/*
		 * Leftover parameters indicate that the system was unable to reverse the 
		 * route properly
		 */
		if ($left) {
			throw new PrivateException('Parameter count exceeded pattern count', 1705221044);
		}
		
		return '/' . implode('/', array_merge($replaced, $add)) . '/';
	}
	
}
