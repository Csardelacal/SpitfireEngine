<?php namespace spitfire\core\router\reverser;

use spitfire\core\Path;

class ClosureReverser implements RouteReverserInterface
{
	
	private $closure;
	
	public function __construct($c) {
		$this->closure = $c;
	}

	/** 
	 * @inheritdoc 
	 */
	public function reverse(Path$path) {
		$c = $this->closure;
		return $c($path);
	}

}
