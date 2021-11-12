<?php

namespace spitfire\io\stream;

use Psr\Http\Message\StreamInterface;
use RuntimeException;
use spitfire\exceptions\ApplicationException;

/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-1301  USA
 */

/**
 * 
 */
class Stream implements StreamInterface
{

	/**
	 * The handle used to manipulate the stream. This class basically is a PSR7
	 * conforming wrapper around this stream.
	 * 
	 * @var resource|null
	 */
	private $handle;
	
	/**
	 * 
	 * @var bool
	 */
	private $seekable;
	
	/**
	 * 
	 * @var bool
	 */
	private $writable;
	
	/**
	 * 
	 * @var bool
	 */
	private $readable;

	/**
	 * List of modes that are either readable or writable, or both.
	 */
	public const WRITABLE = ['w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'];
	public const READABLE = ['w', 'w+', 'a', 'a+', 'r', 'r+'];
	
	/**
	 * 
	 * @param resource $handle
	 * @param bool $seekable
	 * @param bool $writable
	 * @param bool $readable
	 */
	public function __construct($handle, bool $seekable, bool $readable, bool $writable)
	{
		$this->handle = $handle;
		$this->seekable = $seekable;
		$this->writable = $writable;
		$this->readable = $readable;
	}

	/**
	 * Closes the stream. After this method has been invoked, the stream will no longer
	 * be usable.
	 * 
	 * @return void
	 */
	public function close(): void
	{
		/**
		 * If the stream is not attached, let it just not do a thing.
		 */
		if ($this->handle === null) {
			return;
		}

		fclose($this->handle);
		$this->detach();
	}

	/**
	 * Detaches the stream from the underlying stream. Please note that this does not
	 * free the underlying resource.
	 * 
	 * @return resource|null
	 */
	public function detach()
	{
		$handle = $this->handle;

		$this->handle = null;
		$this->readable = false;
		$this->writable = false;
		$this->seekable = false;

		return $handle;
	}

	/**
	 * Returns the size (in bytes) of the stream. If the data is not available, the method
	 * returns a null value.
	 * 
	 * @return int|null
	 */
	public function getSize(): ?int
	{
		if (!$this->handle) {
			return null;
		}

		return fstat($this->handle)['size'] ?? null;
	}

	/**
	 * Tells the current position of the seeking cursor on the stream. If the stream is not seekable 
	 * this will fail with a runtime exception.
	 * 
	 * @throws \RuntimeException on error.
	 * @return int Position of the file pointer
	 */
	public function tell(): int
	{
		if (!$this->handle) {
			throw new RuntimeException('Could not tell on detached stream', 2108041124);
		}

		$result = ftell($this->handle);

		if ($result === false) {
			throw new RuntimeException('Could not tell the stream', 2108041101);
		}

		return $result;
	}

	/**
	 * Returns true if we hit the end of the stream or whether the stream can continue to be 
	 * read from.
	 * 
	 * If the stream is detached, this method returns true. Implying that the stream can not 
	 * be read from any further.
	 * 
	 * @return bool
	 */
	public function eof(): bool
	{

		if (!$this->handle) {
			return true;
		}

		return feof($this->handle);
	}

	/**
	 * Returns true if the stream allows being seeked through. Detached streams are implied to not
	 * be seekable (since they do not exist)
	 * 
	 * @return bool
	 */
	public function isSeekable(): bool
	{
		return $this->handle && $this->seekable;
	}

	/**
	 * Seek to a position in the stream.
	 *
	 * @see http://www.php.net/manual/en/function.fseek.php
	 * 
	 * @throws \RuntimeException on failure.
	 * @param int $offset Stream offset
	 * @param int $whence Specifies how the cursor position will be calculated
	 *     based on the seek offset. Valid values are identical to the built-in
	 *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
	 *     offset bytes SEEK_CUR: Set position to current location plus offset
	 *     SEEK_END: Set position to end-of-stream plus offset.
	 * @return void
	 */
	public function seek($offset, $whence = SEEK_SET): void
	{
		if (!$this->handle) {
			throw new RuntimeException('Could not seek a detached stream', 2108041108);
		}

		if (fseek($this->handle, $offset, $whence) === -1) {
			throw new RuntimeException('Error seeking', 2108041109);
		}
	}

	/**
	 * Seek to the beginning of the stream.
	 *
	 * If the stream is not seekable, this method will raise an exception;
	 * otherwise, it will perform a seek(0).
	 *
	 * @see seek()
	 * @see http://www.php.net/manual/en/function.fseek.php
	 * @throws \RuntimeException on failure.
	 * 
	 * @return void
	 */
	public function rewind(): void
	{
		$this->seek(0);
	}

	/**
	 * If the stream can be written to, this method will return true.
	 * 
	 * @return bool
	 */
	public function isWritable()
	{
		return $this->handle && $this->writable;
	}

	/**
	 * Write data to the stream.
	 *
	 * @throws \RuntimeException on failure.
	 * 
	 * @param string $string The string that is to be written.
	 * @return int Returns the number of bytes written to the stream.
	 */
	public function write($string): int
	{
		$result = fwrite($this->handle, $string);

		if ($result === false) {
			throw new RuntimeException('Cannot write to the stream', 2108041113);
		}

		return $result;
	}

	/**
	 * If the stream can be read from, this method will return true.
	 * 
	 * @return bool
	 */
	public function isReadable()
	{
		return $this->handle && $this->readable;
	}

	/**
	 * Read data from the stream. Please note that this will read from the position
	 * of the seek cursor onward.
	 *
	 * @throws \RuntimeException on failure.
	 * 
	 * @param int $length Read up to $length bytes from the object and return
	 *     them. Fewer than $length bytes may be returned if underlying stream
	 *     call returns fewer bytes.
	 * @return string Returns the data read from the stream, or an empty string if 
	 *     no bytes are available.
	 */
	public function read($length): string
	{
		if (!$this->handle) {
			throw new RuntimeException('Could not read a detached stream', 2108041120);
		}
		
		if ($length === 0) {
			return '';
		}
		
		$result = fread($this->handle, $length);

		if ($result === false) {
			throw new RuntimeException(sprintf('Cannot read %d bytes from the stream', $length), 2108041113);
		}

		return $result;
	}
	
	/**
	 * Reads the remaining data from the stream into a string and returns it.
	 * 
	 * @return string
	 */
	public function getContents() : string
	{
		if (!$this->handle) {
			throw new RuntimeException('Could not read a detached stream', 2108041119);
		}
		
		$result = stream_get_contents($this->handle);
		
		if ($result === false) {
			throw new RuntimeException('Stream could not be read from', 2108041118);
		}
		
		return $result;
	}
	
    /**
	 * Get stream metadata as an associative array or retrieve a specific key.
	 *
	 * The keys returned are identical to the keys returned from PHP's
	 * stream_get_meta_data() function.
	 *
	 * @see http://php.net/manual/en/function.stream-get-meta-data.php
	 * @param string $key Specific metadata to retrieve.
	 * @return array|mixed|null Returns an associative array if no key is
	 *     provided. Returns a specific key value if a key is provided and the
	 *     value is found, or null if the key is not found.
	 */
	public function getMetadata($key = null)
	{
		
		if (!$this->handle) {
			throw new RuntimeException('Could not read metadata on a detached stream', 2108041125);
		}
		
		$meta = stream_get_meta_data($this->handle);
		
		/**
		 * If a key was selected, we return only that key. Whoever came up with 
		 * this was a monster :')
		 */
		if ($key) {
			return $meta[$key]?? null;
		}
		
		return $meta;
	}

	/**
	 * This is a convenience method to allow the printing of streams directly to the
	 * output, making it more convenient.
	 * 
	 * @throws ApplicationException
	 * @return string
	 */
	public function __toString(): string
	{
		/**
		 * Rewind the stream to the beginning, so we can print the data from the
		 * start and not begin somewhere in the middle of the stream.
		 */
		$this->seek(0);
		$result = stream_get_contents($this->handle);

		/**
		 * In case the stream was not readable, we will stop the application from doing
		 * so. This prevents PHP from throwing a cryptic (not a string) kind of error
		 * when returning.
		 */
		if ($result === false) {
			throw new ApplicationException('Stream could not be read', 2108041023);
		}

		return $result;
	}

	/**
	 * 
	 * @param string|StreamInterface $str
	 * @return StreamInterface
	 */	
	public static function fromString($str): StreamInterface
	{
		/**
		 * We require the input to this to either be a string, or a Stream already.
		 */
		assert($str instanceof StreamInterface || is_string($str));

		/**
		 * We already have a stream implementing the stream interface, why convert it to
		 * another stream? Please note here that this may or may not be an instance of
		 * this specific Stream class
		 */
		if ($str instanceof StreamInterface) {
			return $str;
		}

		/**
		 * We create a handle to a temporary stream. Please note that we have a small modification 
		 * to prevent PHP from prematurely writing to a temp-file. By default, PHP will write
		 * data to a temporary file as soon as the stream hits 2MB.
		 * 
		 * We do not want that, since many of our responses work with data that can be a bit over
		 * 2MB, but modern servers do have enough RAM to handle a bit more data in memory.
		 * 
		 * The magic number stands for 16MB.
		 */
		$handle = fopen('php://temp/maxmemory:16777216', 'w+');
		
		if (!$handle) {
			throw new RuntimeException('Could not allocate stream', 2108041130);
		}
		
		$meta = stream_get_meta_data($handle);

		/**
		 * Instance the new stream.
		 */
		return new Stream(
			$handle,
			$meta['seekable'],
			array_search($meta['mode'], self::READABLE, true) !== false,
			array_search($meta['mode'], self::WRITABLE, true) !== false
		);
	}
}
