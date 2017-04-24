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
			
			if ($k) { $target[] = function ($app, $controller, $action, $object) use ($k) { return is_array($$k)? implode('', $$k) : $$k; }; }
			else    { $target[] = function () use ($e) { return $e->getPattern()[0]; }; }
		}
		
		return new ClosureReverser(function ($app, $controller, $action, $object, $env) use ($for, $target) {
			
			foreach ($for as $k => $e) {
				if (\Strings::startsWith($e, ':'))     { continue; }
				elseif ((array)$$k != (array)$e)       { return false; }
			}
			
			return '/' . implode('/', array_map(function ($e) use($app, $controller, $action, $object) { return $e($app, $controller, $action, $object); }, $target));
		});
	}
	
	
}