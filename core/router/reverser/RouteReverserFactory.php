<?php namespace spitfire\core\router\reverser;

use Closure;
use spitfire\core\router\Pattern;

class RouteReverserFactory
{
	
	public static function make($for, $from) {
		if ($for instanceof Closure) { return null; }
		if (is_array($for))          { return self::makeFromArray($for, $from); }
		if (is_string($for))         { return null; }
	}
	
	public static function makeFromArray($for, $from) {
		$pattern = array_map(function ($e) { return new Pattern($e); }, array_filter(explode('/', $from)));
		$target  = [];
		
		foreach ($pattern as /*@var $e Pattern*/$e) {
			$k = array_search(':' . $e->getName(), $for);
			
			/*
			 * The idea behind this is that when the user has defined the parameter
			 * as a controller, action, object or app this closure will return the
			 * replaced parameter.
			 * 
			 * Otherwise a simpler closure will be generated that just returns the
			 * item itself.
			 * 
			 * At the time of writing this system does not support multi-object 
			 * controllers or similar. Which would cause the system to ignore the 
			 * route and fail.
			 */
			$target[] = $k? function ($app, $controller, $action, $object) use ($k) { return is_array($$k)? implode('', $$k) : $$k; } :
			                function () use ($e) { return $e->getPattern()[0]; };
		}
		
		return new ClosureReverser(function ($app, $controller, $action, $object, $env) use ($for, $target) {
			
			/**
			 * Check if the URL can be reverted by this function. If that's not the
			 * case we'll return false and be done.
			 */
			foreach ($for as $k => $e) {
				if (\Strings::startsWith($e, ':')) { continue; }
				if ((array)$$k != (array)$e)       { return false; }
			}
			
			return '/' . implode('/', array_map(function ($e) use($app, $controller, $action, $object) { return $e($app, $controller, $action, $object); }, $target));
		});
	}
	
	
}