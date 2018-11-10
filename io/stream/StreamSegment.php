<?php namespace spitfire\io\stream;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class StreamSegment implements StreamReaderInterface, SeekableStreamInterface
{
	
	private $src;
	
	private $start;
	
	private $end;
	
	private $cursor;
	
	public function __construct(StreamReaderInterface$src, $start, $end = null) {
		$this->src = $src;
		$this->start = $this->cursor = $start;
		$this->end = $end;
		
		if ($this->start >= $this->end) {
			throw new \spitfire\exceptions\OutOfBoundsException('Start of stream segment is out of bounds', 1811081804);
		}
		
		if (!$this->src instanceof SeekableStreamInterface) {
			throw new \InvalidArgumentException('Segments only work on streams that allow seeking', 1811101243);
		}
		
		$this->src->seek($this->start);
	}
	
	public function length(): int {
		if ($this->end) {
			return $this->end - $this->start;
		}
		else {
			return $this->src->length() - 1 - $this->start;
		}
	}

	public function read($length = null) {
		
		if ($this->end) {
			$max = $this->end - $this->src->tell();
			
			if ($max <= 0) {
				return '';
			}
			
			$read = $this->src->read($length);
			
			if (isset($read[$max])) {
				return substr($read, 0, $max);
			}
			else {
				return $read;
			}
		}
		
		else {
			return $this->src->read($length);
		}
		
		
	}

	public function seek($position): StreamInterface {
		$this->src->seek($position + $this->start);
		
		return $this;
	}

	public function tell(): int {
		return $this->src->tell() - $this->start;
	}

}