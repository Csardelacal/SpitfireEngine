<?php namespace spitfire\core\router\reverser;

class ClosureReverser implements RouteReverserInterface
{
	
	private $closure;
	
	public function __construct($c) {
		$this->closure = $c;
	}
	
	public function reverse($app, $controller, $action, $object, $additional = Array()) {
		$c = $this->closure;
		return $c($app, $controller, $action, $object, $additional);
	}

}
