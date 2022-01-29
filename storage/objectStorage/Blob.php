<?php namespace spitfire\storage\objectStorage;

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

class Blob
{
	
	/**
	 *
	 * @var DriverInterface
	 */
	private $mount;
	private $key;
	private $scheme;
	
	
	public function __construct($scheme, $mount, $key)
	{
		$this->scheme = $scheme;
		$this->mount = $mount;
		$this->key = $key;
	}
	
	public function exists()
	{
		return $this->mount->contains($this->key);
	}
	
	public function read()
	{
		return $this->mount->read($this->key);
	}
	
	public function write($content)
	{
		return $this->mount->write($this->key, $content);
	}
	
	public function delete()
	{
		return $this->mount->delete($this->key);
	}
	
	public function uri()
	{
		return $this->scheme . '://' . $this->key;
	}
	
	public function publicURL($ttl = null)
	{
		return $this->mount->url($this->key, $ttl);
	}
	
	public function mime()
	{
		return $this->mount->mime($this->key);
	}
	
	public function length()
	{
		return $this->mount->length($this->key);
	}
	
	public function stream()
	{
		return $this->mount->stream($this->key);
	}
	
	public function isWritable()
	{
		return !$this->mount->readonly($this->key);
	}
}
