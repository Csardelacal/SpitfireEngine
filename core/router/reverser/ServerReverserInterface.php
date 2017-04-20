<?php namespace spitfire\core\router\reverser;

/**
 * {Needs doc}
 */
interface ServerReverserInterface
{
	
	/**
	 * 
	 * @param string[] $parameters
	 * @return bool|string The rewriten server URI or 
	 */
	function reverse($parameters);
}