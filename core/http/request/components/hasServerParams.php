<?php namespace spitfire\core\http\request\components;

trait hasServerParams
{
	
	/**
	 * The server parameter array contains all the runtime information the server
	 * has about itself and the current request. Usually this data is directly
	 * originated from $_SERVER
	 *
	 * @var string[]
	 */
	private $serverParams;
	
	/**
	 * The server params function returns the equivalent of the _SERVER
	 * superglobal. We generally set the content of the variable to _SERVER
	 * but you may come across implementations that override it completely
	 * or partially.
	 *
	 * @return string[]
	 */
	public function getServerParams()
	{
		return $this->serverParams;
	}
}
