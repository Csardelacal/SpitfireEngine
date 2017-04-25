<?php namespace spitfire\core\router\reverser;

use spitfire\core\router\Pattern;
use spitfire\core\router\Server;

class BaseServerReverser implements ServerReverserInterface
{
	/** @var Pattern[] */
	private $pattern;
	/** @var Server */
	private $server;

	/**
	 * @param Pattern[] $pattern
	 * @param Server    $server
	 */
	public function __construct($pattern, $server) {
		$this->pattern = $pattern;
		$this->server  = $server;
	}

	/** @inheritdoc */
	public function reverse($parameters) {
		$result = Array();
		
		foreach ($this->pattern as $p) {
			echo $p->getName(), is_array($p->getPattern()) ? implode('|', $p->getPattern()) : $p->getPattern(), PHP_EOL;
			/*@var $p \spitfire\core\router\Pattern*/
			if     (!$p->getName())                    { $result[] = $p->getPattern()[0]; }
			elseif (isset($parameters[$p->getName()])) { $result[] = $parameters[$p->getName()]; }
			else                                       { return false; }
		}
		
		return implode('.', $result);
	}

	/** @inheritdoc */
	public function getServer() {
		return $this->server;
	}

}
