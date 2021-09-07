<?php namespace spitfire\storage\drive;

use BadMethodCallException;
use spitfire\storage\objectStorage\DriverInterface;
use spitfire\storage\objectStorage\IOStream;
use function mime;

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

class Driver implements DriverInterface
{
	
	/**
	 * This "encages" the application, preventing it from writing outside the scope
	 * of the storage mechanism.
	 *
	 * @var string 
	 */
	private $path;
	
	public function __construct($dsn) {
		$this->path = rtrim($dsn, '\/') . DIRECTORY_SEPARATOR;
		
		if (\spitfire\utils\Strings::startsWith($this->path, '@')) {
			$this->path = spitfire()->locations()->storage(substr($this->path, 1), '\/');
		}
	}

	public function atime($key) {
		return fileatime($this->path . $key);
	}

	public function contains($key) {
		return file_exists($this->path . $key) && !is_dir($this->path . $key);
	}

	public function delete($key) {
		return unlink($this->path . $key);
	}

	public function mime($key) {
		return mime($this->path . $key);
	}
	
	/**
	 * Returns the size (in bytes) of a certain file on the file-system.
	 * 
	 * @todo Check the behavior of this method with stuff like /dev/one or similar
	 * @param string $key
	 * @return int
	 */
	public function length($key) {
		return filesize($this->path . $key);
	}

	public function mtime($key) {
		return filemtime($this->path . $key);
	}

	public function read($key) {
		return file_get_contents($this->path . $key);
	}

	public function stream($key): IOStream {
		return new IOStream(new FileStreamReader($this->path . $key), new FileStreamWriter($this->path . $key));
	}

	public function url($key, $ttl) {
		throw new BadMethodCallException();
	}

	public function write($key, $contents, $ttl = null) {
		$full = $this->path . $key;
		$dir = dirname($full);
		
		if (!is_dir($dir)) {
			mkdir($dir, 0777, true);
		}
		
		return file_put_contents($full, $contents);
	}

	public function readonly($key) {
		return !((!file_exists($this->path . $key) && is_writable($this->path . dirname($key))) || is_writable($this->path . $key));
	}

}
