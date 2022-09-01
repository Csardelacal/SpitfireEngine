<?php namespace spitfire\core\http\request\components;

use spitfire\core\Request;

trait hasParsedBody
{
	
	/**
	 *
	 * @var mixed
	 */
	private $parsedBody;
	
	/**
	 *
	 * @param null|mixed[] $data
	 */
	public function withParsedBody($data) : Request
	{
		$copy = clone $this;
		$copy->parsedBody = $data;
		return $copy;
	}
	
	/**
	 *
	 * @return mixed[]
	 */
	public function getParsedBody()
	{
		return $this->parsedBody;
	}
}
