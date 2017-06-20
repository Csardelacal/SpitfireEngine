<?php namespace spitfire\core\router\reverser;

use Exception;
use spitfire\core\Path;
use spitfire\core\router\ParametrizedPath;
use spitfire\core\router\URIPattern;

/**
 * The current state of the router really makes this class unnecessary as crud,
 * and therefore I'll be doing my best to remove it in a timely manner.
 * 
 * @deprecated since version 0.1-dev 20170616
 */
class RouteReverserFactory
{
	
	public static function make($for, $from) {
		if ($for instanceof ParametrizedPath) { return self::makeFromPath($for, $from); }
		
		return null;
	}
	
	/**
	 * 
	 * @param ParametrizedPath $for
	 * @param type $from
	 * @return ClosureReverser
	 */
	public static function makeFromPath($for, $from) {
		$p = URIPattern::make($from);
		
		return new ClosureReverser(function ($path) use ($for, $p) {
			try {
				return $p->reverse($for->extract($path));
			} catch (Exception$e) {
				return false;
			}
		});
	}
	
	
}