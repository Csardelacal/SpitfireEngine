<?php namespace tests\storage\drive;

use PHPUnit\Framework\TestCase;
use spitfire\storage\drive\Directory;
use spitfire\storage\drive\Driver;
use spitfire\storage\drive\File;
use spitfire\storage\drive\MountPoint;
use spitfire\storage\objectStorage\DirectoryInterface;
use spitfire\storage\objectStorage\DriveDispatcher;
use spitfire\storage\objectStorage\FileInterface;
use function storage;

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

class NewDriveTest extends TestCase
{
	
	private $storage;
	private $string = 'Hello world';
	
	
	public function testRead() {
		$this->storage = new DriveDispatcher;
		$this->storage->register('file', new Driver('/'));
		file_put_contents('/tmp/test.txt', 'Hello world');
		$blob = $this->storage->retrieve('file:///tmp/test.txt');
		
		$this->assertNotEmpty($blob->read());
		$this->assertNotEmpty($blob->uri());
		$this->assertEquals(strlen($this->string), $blob->stream()->writer()->write($this->string));
		$blob->stream()->writer()->close();
		$this->assertEquals(substr($this->string, 0, 5), $blob->stream()->reader()->read(5));
	}
}