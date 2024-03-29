<?php namespace spitfire\core\router;

use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\utils\Strings;

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
	 * The pattern is the string that we were given to extract data from the
	 * URL. This is mostly used for debugging and information purposes, letting
	 * the developer know which rule we're working on.
	 */
	private $pattern;
	
	/**
	 * The subpatterns array allows the application to override the standard search
	 * pattern to allow for more intricate expressions. For example, the application
	 * could determine that it will only accept alphanumeric characters, a single
	 * character, or similar.
	 * 
	 * By default, routes will capture anything but slashes.
	 * 
	 * @var string[]
	 */
	private array $subpatterns = [];
	
	/**
	 * These are the variables that the user defined in their URL pattern. These are
	 * matched and returned to the route.
	 *
	 * NOTE: PHPStan would notice that the arrays this collection contains (not the
	 * collection itself) can never be empty. It was pretty adamant about the fact
	 * that a Collection<array> cannot contain non-empty-array for some reason.
	 *
	 * @var Collection<non-empty-array<int,string>>
	 */
	private $variables;
	
	/**
	 * Indicates whether this is an open ended pattern. This implies that it'll
	 * accept URL that are longer than the patterns it can test with (these
	 * additional parameters will get appended to the object).
	 *
	 * @var boolean
	 */
	private $open;
	
	/**
	 * Instances a new URI Pattern. This class allows your application to test
	 * whether a URL matches the pattern you gave to the constructor.
	 *
	 * @param string $pattern
	 */
	public function __construct($pattern)
	{
		
		if (!Strings::startsWith($pattern, '/')) {
			$pattern = sprintf('/%s', $pattern);
		}
		
		/*
		* If the pattern ends in a slash it's not considered open ended, this is
		* important for how we parse the pattern.
		*/
		$this->open = !Strings::endsWith($pattern, '/');
		$this->pattern = $pattern;
		
		/**
		 * A second, very similar expression, is used to extract the variables that we need.
		 */
		preg_match_all('/\{([^\}]+)\}/', $pattern, $matches);
		
		$this->variables = (Collection::fromArray($matches[1]))->each(function (string $match) : array {
			return explode(':', $match, 2);
		});
	}
	
	public function where(string $var, string $matches) : void
	{
		$this->subpatterns[$var] = $matches;
	}
	
	public function compile() : string
	{
		
		
		/**
		 * Depending on whether our pattern is terminated (meaning it ends in a forward slash),
		 * we will enforce that our route only matches urls that match completely, or whether we
		 * allow routes to have extra payload.
		 */
		$template = $this->open? '#^%s(.*)$#' : '#^%s$#';
		
		
		/**
		 * Since SF0.2, we've started using handlebars to match URL components. This has a
		 * series of advantages:
		 *
		 * 1. Handlebars do not usually occurr on URLs
		 * 2. We can match patterns that contain prefixed components like `/user/@username` without black magic
		 *
		 * To do so, we generate a regular expression from the pattern we received, that we
		 * can now use to test the routes and see whether they match.
		 * 
		 * Note: The `(?:/|$)` expression is a non capturing group (not a lookaround). For
		 * some reason I forgot this fact and it's taken me a while to relearn
		 * 
		 * @see https://www.php.net/manual/en/regexp.reference.subpatterns.php
		 * 
		 * > The fact that plain parentheses fulfill two functions is not always helpful. 
		 * > There are often times when a grouping subpattern is required without a capturing
		 * > requirement. If an opening parenthesis is followed by "?:", the subpattern does 
		 * > not do any capturing, and is not counted when computing the number of any subsequent
		 * > capturing subpatterns. 
		 */
		return sprintf($template, preg_replace_callback(
			['/\//', '/\{([^\}]+)\}/'],
			function (array $match) : string {
				switch(count($match)) {
					case 1: 
						# When a slash is matched in the expression, it's either a slash
						# or the end of the matches.
						return '(?:/|$)';
					case 2:
						# When a variable is passed, we check if the user provided an override
						# for it
						list($var) = explode(':', $match[1]);
						return sprintf('(%s)', $this->subpatterns[$var]?? '[^\/]*');
				}
				throw new ApplicationException('Bad pattern');
			},
			$this->pattern
		));
	}
	
	/**
	 * Tests whether a given $uri matches the patterns that this object holds.
	 * Please note that if the URI is too long and the pattern is not open
	 * ended it will throw a missmatch exception.
	 *
	 * @param string $uri
	 * @return \spitfire\core\router\Parameters
	 */
	public function test(string $uri) :? Parameters
	{
		
		if (!preg_match_all($this->compile(), $uri, $raw)) {
			return null;
		}
		
		/**
		 * Preg_match_all's return is really nasty to work with, it contains a multidimensional array,
		 * which in our case always contains 1 item in the second layer, something like this
		 *
		 * array(4) {
		 *   [0]=>
		 *   array(1) {
		 *     [0]=>
		 *     string(59) "..."
		 *   }
		 *   [1]=>
		 *   array(1) {
		 *     [0]=>
		 *     string(5) "..."
		 *   }
		 *   ...
		 * }
		 *
		 * We use this function to conver it into something easier to work with by removing the intermediate
		 * layer. Leaving us with something like this:
		 *
		 * array(4) {
		 *   [0]=>
		 *   string(59) "..."
		 *   [1]=>
		 *   string(5) "..."
		 *   ...
		 * }
		 *
		 * @var string[]
		 */
		$matches = array_map(function ($e) {
			return $e[0];
		}, $raw);
		
		$params = new Parameters();
		
		/**
		 * The first match contains the entire URL, since it will match the complete
		 * regular expression. Meaning that we have to discard it to keep the variables
		 * that we need
		 */
		array_shift($matches);
		
		/**
		 * The rest of the array contains the matches that we found. This is a bit of a complicated
		 * expression, but the gist of it is really simple.
		 *
		 * - Every variable contains an array with a name and an optional default value in the keys 0 and 1
		 * - preg_match writes the result of every capturing group into the matches
		 *   array as another array(since we have no nesting or similar they only have one key)
		 * - We combine the variable name with either the match or the default for the variable
		 */
		foreach ($this->variables as $var) {
			$match = array_shift($matches);
			$params->addParameter($var[0], $match?: $var[1]?? null);
		}
		
		if (!empty($matches)) {
			$params->setUnparsed(explode('/', array_shift($matches)));
		}
		
		return $params;
	}
	
	/**
	 * Takes a parameter list and constructs a string URI from the combination
	 * of patterns and parameters.
	 *
	 * @param string[] $parameters
	 * @return string
	 * @throws ApplicationException
	 */
	public function reverse($parameters)
	{
		/**
		 * A second, very similar expression, is used to extract the variables that we need.
		 */
		$fn = function ($match) use ($parameters) {
			$pieces = explode(':', $match[1], 2);
			$variable = reset($pieces);
			
			if (!array_key_exists($variable, $parameters)) {
				throw new ApplicationException(sprintf('Undefined URL variable %s in %s', $variable, $this->pattern), 2110141646);
			}
			
			return $parameters[$variable];
		};
		
		return preg_replace_callback('/\{([^\}]+)\}/', $fn, $this->pattern);
	}
	
	public static function make($str)
	{
		if ($str instanceof URIPattern) {
			return $str;
		}
		elseif (is_string($str)) {
			return new URIPattern($str);
		}
		else {
			throw new \InvalidArgumentException('Invalid pattern for URIPattern::make', 1706091621);
		}
	}
}
