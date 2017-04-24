<?php namespace spitfire\core\router\reverser;

use spitfire\App;

class ClosureReverser implements RouteReverserInterface
{
	
	private $closure;
	
	public function __construct($c) {
		$this->closure = $c;
	}

	/** @inheritdoc */
	public function reverse($app, $controller, $action, $object, $additional = Array()) {
		$c = $this->closure;
		if ($app instanceof App)
			$app = $app->getNameSpace();
		return $c($app, $controller, $action, $object, $additional);
	}

}
