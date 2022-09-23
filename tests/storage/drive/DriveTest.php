<?php namespace tests\storage\drive;

use League\Flysystem\Filesystem as FlysystemFilesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use PHPUnit\Framework\TestCase;
use spitfire\io\stream\Stream;
use spitfire\storage\drive\File;
use spitfire\storage\DriveDispatcher;
use spitfire\storage\FileSystem;

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
	
	public function setUp() : void
	{
		parent::setUp();
		
		$this->storage = new DriveDispatcher();
		$this->storage->register('tests', new FileSystem(
			new FlysystemFilesystem(
				new LocalFilesystemAdapter(sys_get_temp_dir())
			)
		));
		
		$this->storage->write('tests://test/test.txt', 'Hello');
	}
	
	public function testOpenDrive()
	{
		$dir = $this->storage->readStream('tests://test/');
		
		$this->assertInstanceOf(Stream::class, $dir);
	}
	
	/**
	 *
	 * @depends testOpenDrive
	 */
	public function testCreateFile()
	{
		$this->storage->writeStream('tests://test.txt', Stream::fromString($this->string));
		$this->assertEquals($this->string, $this->storage->read('tests://test.txt'));
		
		$file = $this->storage->readStream('tests://test.txt');
		$this->assertInstanceOf(Stream::class, $file);
	}
	
	/**
	 *
	 * @depends testCreateFile
	 * @param File $file
	 */
	public function testReadFile()
	{
		$this->storage->writeStream('tests://test.txt', Stream::fromString($this->string));
		
		$uri  = 'tests://test.txt';
		$read = $this->storage->read($uri);
		
		$this->assertEquals($this->string, $read);
	}
	
	/**
	 * When a stream gets written to a file, the resulting stream should mimic the file's
	 * permissions.
	 */
	public function testReadFileWithRecycledHandle()
	{
		$stream = $this->storage->writeStream('tests://test.txt', Stream::fromString($this->string));
		$stream->rewind();
		
		$this->assertEquals($this->string, $stream->getContents());
	}
	
	
	/**
	 *
	 * @depends testReadFile
	 * @param File $file
	 */
	public function testDeleteFile()
	{
		$this->storage->writeStream('tests://test.txt', Stream::fromString($this->string));
		$this->assertEquals(true, $this->storage->has('tests://test.txt'));
		$this->storage->delete('tests://test.txt');
		$this->assertEquals(false, $this->storage->has('tests://test.txt'));
	}
	
	public function tearDown() : void
	{
		parent::tearDown();
		
		$this->storage->unregister('tests://');
	}
}
