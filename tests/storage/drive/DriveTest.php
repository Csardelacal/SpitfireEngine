<?php namespace tests\storage\drive;

use PHPUnit\Framework\TestCase;
use spitfire\storage\drive\Directory;
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

class DriveTest extends TestCase
{
	
	private $storage;
	private $string = 'Hello world';
	
	public function setUp() : void {
		parent::setUp();
		
		$this->storage = new DriveDispatcher;
		$this->storage->register('tests', new \spitfire\storage\drive\Driver(sys_get_temp_dir()));
		
		$this->storage->retrieve('tests://test/test.txt')->write('Hello');
	}
	
	public function testOpenDrive() {
		$dir = $this->storage->retrieve('tests://test/');
		
		$this->assertInstanceOf(\spitfire\storage\objectStorage\Blob::class, $dir);
		
		/*
		 * Test that the path, and URI are exactly the same when we retrieve the file
		 * via URI and via the object oriented interface
		 */
		$this->assertEquals($dir->uri(), $this->storage->retrieve('tests://test/')->uri());
		$this->assertEquals($dir->uri(), 'tests://test/');
	}
	
	/**
	 * 
	 * @depends testOpenDrive
	 */
	public function testCreateFile() {
		$file = $this->storage->retrieve('tests://test.txt');
		$this->assertInstanceOf(\spitfire\storage\objectStorage\Blob::class, $file);
		
		
		$file->write($this->string);
		$this->assertEquals($this->string, $file->read());
		
		return $file;
	}
	
	/**
	 * 
	 * @depends testCreateFile
	 * @param File $file
	 */
	public function testReadFile(\spitfire\storage\objectStorage\Blob$file) {
		$uri  = 'tests://test.txt';
		$read = $this->storage->retrieve($uri);
		
		$this->assertEquals($uri, $read->uri());
		$this->assertInstanceOf(\spitfire\storage\objectStorage\Blob::class, $read);
		$this->assertEquals($this->string, $read->read());
		
		return $file;
	}
	
	/**
	 * 
	 * @depends testReadFile
	 * @param File $file
	 */
	public function testDeleteFile(\spitfire\storage\objectStorage\Blob$file) {
		$file->delete();
		$this->assertEquals(false, $this->storage->retrieve('tests://test.txt')->exists());
	}
	
	/**
	 * 
	 * @depends testCreateFile
	 */
	public function testContains() {
		$this->assertEquals(false, $this->storage->retrieve('tests://test/')->exists());
		$this->assertEquals(true, $this->storage->retrieve('tests://test/test.txt')->exists());
		$this->assertEquals(false, $this->storage->retrieve('tests://test/nada.file')->exists());
	}
	
	public function tearDown() : void {
		parent::tearDown();
		
		$this->storage->unregister('tests://');
	}
}