<?php namespace spitfire\core;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */

use InvalidArgumentException;
use JsonException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use spitfire\io\stream\Stream;
use spitfire\io\template\View;

class ResponseFactory implements ResponseFactoryInterface
{
	
	/**
	 * Creates a new response with an empty body.
	 * 
	 * @throws RuntimeException
	 * @throws InvalidArgumentException
	 */
	public function createResponse(int $code = 200, string $reasonPhrase = ''): Response
	{
		return new Response(
			Stream::fromString(''),
			$code,
			$reasonPhrase,
			[]
		);
	}
	
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
	 * @throws RuntimeException
	 * @param string $identifier
	 * @param array<string,mixed> $data
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
	 * @throws RuntimeException
	 * @throws JsonException
	 * @param array<scalar,mixed> $data
	 * @return StreamInterface
	 */
	public function json(array $data) : StreamInterface
	{
		return Stream::fromString(json_encode($data, JSON_THROW_ON_ERROR));
	}
}
