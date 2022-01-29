<?php namespace spitfire\storage\objectStorage;

use spitfire\io\stream\StreamReaderInterface;
use spitfire\io\stream\StreamWriterInterface;
use spitfire\storage\drive\exceptions\FilePermissionsException;

/* 
 * The MIT License
 *
 * Copyright 2020 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class IOStream
{
	
	private $reader;
	private $writer;
	
	public function __construct(StreamReaderInterface$reader, StreamWriterInterface$writer = null)
	{
		$this->reader = $reader;
		$this->writer = $writer;
	}
	
	public function reader() : StreamReaderInterface
	{
		return $this->reader;
	}
	
	public function writer() : StreamWriterInterface
	{
		if (!$this->writer) {
			throw new FilePermissionsException('No writable stream', 2001161650);
		}
		
		return $this->writer;
	}
}
