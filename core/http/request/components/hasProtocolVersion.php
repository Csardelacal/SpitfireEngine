<?php namespace spitfire\core\http\request\components;

trait hasProtocolVersion
{
	
	/**
	 * The version of the HTTP protocol being used for this request.
	 *
	 * @var string
	 */
	private $version = '1.1';
	
	/**
	 * Returns the version of the HTTP protocol used to send this request. For PHP most of this
	 * is transparent when handling a request, so it's possible that this data is actually not
	 * properly poulated.
	 *
	 * @return string
	 */
	public function getProtocolVersion() : string
	{
		return $this->version;
	}
}
