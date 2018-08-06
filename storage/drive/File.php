<?php namespace spitfire\storage\drive;

use spitfire\storage\objectStorage\FileInterface;
use spitfire\storage\objectStorage\DirectoryInterface;

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

class File implements FileInterface
{
	
	private $path;
	
	public function __construct($path) {
		$this->path = $path;
	}
	
	public function delete(): bool {
		return unlink($this->path);
	}

	public function exists(): bool {
		return file_exists($this->path);
	}

	public function getParent(): DirectoryInterface {
		return new Directory(dirname($this->path));
	}

	public function isWritable(): bool {
		return file_exists($this->path) && is_writable($this->path);
	}

	public function move(DirectoryInterface $to, string $name): FileInterface {
		/*
		 * If the target is a directory we can directly move the file on the drive,
		 * therefore we don't have to get the file from the drive a second time.
		 */
		if ($to instanceof Directory) { rename($this->path, $to->get($name)->getURI()); }
		else                          { $to->get($name)->write($this->read()); }
		
		return $to->get($name);
	}
	
	public function read(): string {
		return file_get_contents($this->path);
	}
	
	public function write(string $data): bool {
		return file_put_contents($this->path, $data);
	}

	public function getURI() : string {
		return 'file://' . $this->path;
	}

	public function mime(): string {
		return mime_content_type($this->path);
	}

}
