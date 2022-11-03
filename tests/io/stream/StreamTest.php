<?php namespace tests\spitfire\io\stream;

use PHPUnit\Framework\TestCase;
use spitfire\io\stream\Stream;

class StreamTest extends TestCase
{
	
	
	private $string = 'Hello world';
	
	public function testDetach()
	{
		$stream = Stream::fromString($this->string);
		$handle = $stream->detach();
		
		$this->assertEquals($this->string, fread($handle, 8000));
	}
	
	public function testStringiFyNonSeekable()
	{
		$handle = fopen('php://memory', 'r+');
		$stream = new Stream($handle, false, true, true);
		$this->assertEquals('', (string)$stream);
	}
}
