<?php namespace spitfire\core\router\reverser;

use spitfire\App;

class ClosureReverser implements RouteReverserInterface
{
	
	private $closure;
	
	public function __construct($c) {
		$this->closure = $c;
	}

	/** 
	 * @inheritdoc 
	 */
	public function reverse($app, $controller, $action, $object, $additional = Array()) {
		$c = $this->closure;
		
		#If the application is providing an app object to reverse the route, then
		#it should be returned to it's string format.
		if ($app instanceof App) {	$app = $app->getNameSpace(); }
		
		return $c($app, $controller, $action, $object, $additional);
	}

}
