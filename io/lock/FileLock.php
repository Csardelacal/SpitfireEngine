<?php namespace spitfire\io\lock;

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

class FileLock implements LockInterface
{
	
	private $file;
	private $handle;
	
	public function __construct($file) {
		$this->file = $file;
		$this->handle = fopen($file, file_exists($file)? 'r' : 'w+');
	}
	
	public function lock($wait = true): LockInterface {
		if ($wait) { flock($this->handle, LOCK_EX); }
		elseif(!flock($this->handle, LOCK_EX | LOCK_NB)) { throw new LockUnavailableException('Could not obtain lock', 2001311655); }
		
		return $this;
	}

	public function unlock() {
		flock($this->handle, LOCK_UN);
		return $this;
	}

	public function synchronize($fn, $wait = true) {
		try {
			$this->lock($wait);
			$fn();
			$this->unlock();
		} 
		catch (LockUnavailableException$ex) { return; }
	}

}
