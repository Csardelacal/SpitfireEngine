<?php namespace spitfire\core;

use Psr\Http\Message\StreamInterface;
use spitfire\io\stream\Stream;
use spitfire\io\template\View;

class ResponseFactory
{
	

	/**
	 * Creates a view. This function will automatically locate the appropriate file
	 * containing the template for the view, generate a template and render it to
	 * generate a response.
	 *
	 * Right now it does a very simplistic job, intializing a pug engine that will allow
	 * us to generate an output.
	 * 
	 * Originally this was intended to provide way more functionality than just integrating
	 * with pug. Right now, the fact that there is a templating engine, and that it provides
	 * strong features we need out-of-the-box should be perfectly sufficient.
	 *
	 * @param string $identifier
	 * @param array $data
	 * @return StreamInterface
	 */
	public function pug(string $identifier, array $data) : StreamInterface
	{
		$file = spitfire()->locations()->resources('views/' . $identifier . '.pug');
		return Stream::fromString((new View($file, $data))->render());
	}

	/**
	 * Creates a JSON response.
	 *
	 * @param array $data
	 * @return StreamInterface
	 */
	public function json(array $data) : StreamInterface
	{
		return Stream::fromString(json_encode($data, JSON_THROW_ON_ERROR));
	}
}
