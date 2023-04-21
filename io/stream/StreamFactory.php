<?php namespace spitfire\io\stream;

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

use Psr\Http\Message\StreamFactoryInterface;
use RuntimeException;

class StreamFactory implements StreamFactoryInterface
{
	
	/**
	 * Creates a stream from a string. Please note that while streams are efficient
	 * to write to and read from using Streams for short strings will be very memory
	 * intensive.
	 * 
	 * @param string $content
	 * @return Stream
	 */
	public function createStream(string $content = ''): Stream
	{
		return Stream::fromString($content);
	}
	
	/**
	 * Creates a new Stream from a file path.
	 * 
	 * @param string $filename
	 * @param string $mode
	 * @return Stream
	 */
	public function createStreamFromFile(string $filename, string $mode = 'r'): Stream
	{
		assume(
			file_exists($filename),
			fn() => throw new RuntimeException(sprintf('File %s does not exist and cannot be opened', $filename))
		);
		
		assume(
			in_array($mode, Stream::MODES),
			fn() => throw new RuntimeException(sprintf('File %s cannot be opened in %s mode', $filename, $mode))
		);
		
		return Stream::fromHandle(fopen($filename, $mode));
	}
	
	/**
	 * Creates a new Stream from a resource.
	 * 
	 * @param resource $resource
	 * @return Stream
	 */
	public function createStreamFromResource($resource): Stream
	{
		return Stream::fromHandle($resource);
	}
}
