<?php namespace tests\spitfire\core;

use PHPUnit\Framework\TestCase;
use spitfire\io\stream\Stream;

class RedirectOutputTest extends TestCase
{
	
	public function testRedirect()
	{
		$stream = new Stream(fopen('php://memory', 'w'), true, true, true);
		$result = redirectOutput(
			$stream,
			function () {
				echo 'Hello world';
				return 'test';
			}
		);
		$stream->rewind();
		
		$this->assertEquals('Hello world', $stream->getContents());
		$this->assertEquals('test', $result);
	}
}
