<?php namespace tests\spitfire\io\media;

use spitfire\storage\objectStorage\DriveDispatcher;

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

/**
 * This tests Spitfire's ability to handle WEBP files, which are an image format
 * developed by Google to reduce the bandwidth and storage requirements of images
 * while maintaining a good level of detail.
 * 
 * As of April 2020, the format has about 80% adoption across all browsers (source:
 * https://caniuse.com/#search=webp) and has received mainline support from the 
 * PHP core. Which makes it easy to deploy into existing environments.
 */
class WEBPManipulationTest extends \PHPUnit\Framework\TestCase
{
	
	private static $tmpdir = '/tmp';
	private $storage;
	private $filename;
	
	public function setUp(): void
	{
		$this->storage = new DriveDispatcher;
		$this->storage->register('file', new \spitfire\storage\drive\Driver('/'));
		$this->filename = __DIR__ . '/m3w.png';
	}
	
	/**
	 * Loads the PNG file in the folder and writes it to a webp file. If everything
	 * went correctly, the output path should exist.
	 * 
	 * @return type
	 */
	public function testOutputWEBP()
	{
		$img = media()->load($this->storage->retrieve('file:/' . $this->filename));
		$output = $this->storage->retrieve('file:/' . self::$tmpdir . '/test.webp');
		
		$img->store($output);
		$this->assertEquals(true, $output->exists());
		return $output;
	}
	
	/**
	 * Loads the webp file we generated in the previous step and check whether GD
	 * handled the file.
	 * 
	 * @depends testOutputWEBP
	 */
	public function testInputWEBP($output)
	{
		$loaded = media()->load($output);
		$this->assertInstanceOf(\spitfire\io\media\GDManipulator::class, $loaded);
	}
	
	public function tearDown() : void
	{
		//$this->storage->retrieve('file:/' . self::$tmpdir . '/test.webp')->delete();
	}
}
