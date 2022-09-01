<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

trait hasBody
{
	
	/**
	 *
	 * @var StreamInterface
	 */
	private $body;
	
	/**
	 * Returns the content that is to be sent with the body. This is a string your
	 * application has to set beforehand.
	 *
	 * In case you're using Spitfire's Context object to manage the context the
	 * response will get the view it contains and render that before returning it.
	 *
	 * @return StreamInterface
	 */
	public function getBody() : StreamInterface
	{
		return $this->body;
	}
	
	public function withBody(StreamInterface $body) : ServerRequestInterface
	{
		$copy = clone $this;
		$copy->body = $body;
		return $copy;
	}
}
