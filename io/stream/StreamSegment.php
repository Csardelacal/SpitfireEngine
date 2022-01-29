<?php namespace spitfire\io\stream;

use Psr\Http\Message\StreamInterface;
use spitfire\exceptions\ApplicationException;

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

/**
 * The stream segment class allows an application to restrict access to a stream
 * to a specific segment of it.
 * 
 * This class comes in handy when doing HTTP range requests where the sender 
 * determines a segment of a file that it wishes to receive.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class StreamSegment implements StreamInterface
{
	
	/**
	 *
	 * @var StreamInterface
	 */
	private $src;
	
	/**
	 * Determines the first index of the stream that should be returned when
	 * reading from this segment.
	 *
	 * @var int 
	 */
	private $start;
	
	/**
	 * The last index to be read when the application is trying to read the source
	 * stream.
	 *
	 * @var int|null
	 */
	private $end;
	
	/**
	 * 
	 * @param StreamInterface $src
	 * @param int $start
	 * @param int|null $end The last INDEX of the stream to be included
	 * @throws ApplicationException
	 */
	public function __construct(StreamInterface $src, $start, $end = null) 
	{
		$this->src = $src;
		$this->start = $start;
		$this->end = $end;
		
		if ($this->end && $this->start >= $this->end) {
			throw new ApplicationException('Start of stream segment is out of bounds', 1811081804);
		}
		
		/**
		 * We can only wrap segments around seekable and readable streams, since they are the only
		 * streams that we can jump to a beginning and actually read from.
		 */
		assert($this->src->isSeekable());
		assert($this->src->isReadable());
		
		$this->src->seek($this->start);
	}
	
	/**
	 * 
	 * @return int
	 */
	public function getSize(): int 
	{
		/**
		 * If the end is defined, we will try to make the segment reach the end, without
		 * it shooting past the size of the underlying stream
		 */
		if ($this->end) {
			$size = min($this->end + 1, $this->src->getSize());
		}
		else {
			$size = $this->src->getSize();
		}
		
		return $size - $this->start;
	}
	
	/**
	 * Returns true if the stream is readable. Segments are always readable, since their purpose
	 * is to provide a reading fence.
	 * 
	 * @return bool
	 */
	public function isReadable() : bool
	{
		return true;
	}
	
	/**
	 * 
	 * @param int $length
	 * @return string
	 */
	public function read($length = null) : string
	{
		
		/**
		 * The end is either defined by the user, or by the last index available
		 * to a stream.
		 */
		$end = $this->end? $this->end : $this->src->getSize() - 1;
		$max = $end - $this->src->tell() + 1;
		return $this->src->read($length? clamp(0, $length, $max) : $max);
	}
	
	/**
	 * Returns whether the underlying stream is writable. There's not much use to using
	 * stream segments for writing, but they will 'fence' the application, allowing it to
	 * only write within the segment.
	 * 
	 * @return bool
	 */
	public function isWritable() : bool
	{
		return $this->src->isWritable();
	}
	
	/**
	 * Write to the underlying stream. This method will respect the end boundary, which means
	 * that if your application is trying to write 6KB to a 4KB window, only the first 4KB will
	 * be written and the rest discarded.
	 * 
	 * @param string $string
	 * @return int
	 */
	public function write($string) : int
	{
		
		/**
		 * If the segment is bound to an end, we will not allow the application to write past
		 * the end of the segment.
		 */
		if ($this->end) {
			$current = $this->src->tell();
			$max     = $this->end;
			
			return $this->src->write(substr($string, 0, $max - $current));
		}
		
		return $this->src->write($string);
	}
	
	public function rewind() : StreamSegment
	{
		$this->src->seek($this->start);
		return $this;
	}
	
	/**
	 * 
	 * @param int $position
	 * @param int $whence
	 * @return StreamSegment
	 */
	public function seek($position, $whence = SEEK_SET): StreamSegment 
	{
		$this->src->seek($position + $this->start, $whence);
		return $this;
	}
	
	/**
	 * Segments must be seekable, since they depend on the underlying stream being seekable.
	 * 
	 * @return bool
	 */
	public function isSeekable() : bool
	{
		return true;
	}
	
	/**
	 * 
	 * @return int
	 */
	public function tell(): int 
	{
		return $this->src->tell() - $this->start;
	}
	
	/**
	 * Returns whether the stream has been read to it's end. Please note that the underlying
	 * stream may not have reached it's end, but the segment may be exhausted.
	 * 
	 * @return bool
	 */
	public function eof() : bool
	{
		return $this->src->eof() || $this->src->tell() >= $this->end;
	}
	
	/**
	 * Detaches the stream from the underlying resource, making it unable to operate
	 * on the stream that it wraps.
	 * 
	 * @return resource|null
	 */
	public function detach()
	{
		return $this->src->detach();
	}
	
	/**
	 * If we close the segment, we just forward this to the underlying stream, closing it
	 * and freeing it's resources.
	 * 
	 * @return void
	 */
	public function close()
	{
		$this->src->close();
	}
	
	/**
	 * Returns the entire content of the stream segment.
	 * 
	 * @return string
	 */
	public function getContents()
	{
		$this->src->seek($this->start);
		return $this->src->read($this->getSize());
	}
	
	/**
	 * The toString method is a convenience that outputs the stream as a string. In the case
	 * of segments, we will only output the appropriate window.
	 * 
	 * @return string
	 */
	public function __toString()
	{
		$this->src->seek($this->start);
		return $this->src->read($this->getSize());
	}
	
	/**
	 * The metadata of the segment is purely the metadata of the underlying stream. Please note that
	 * information about the size, etc may be incorrect.
	 * 
	 * @return mixed
	 */
	public function getMetadata($key = null)
	{
		return $this->src->getMetadata($key);
	}
	
	/**
	 * 
	 * @return int
	 */
	public function getStart() : int
	{
		return $this->start;
	}
	
	/**
	 * 
	 * @return int
	 */
	public function getEnd() : int
	{
		/**
		 * The minus one is to convert the length into the offset.
		 */
		return $this->end? $this->end : $this->start + $this->getSize() - 1;
	}
}
