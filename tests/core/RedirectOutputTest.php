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
	
	public function testExceptionsWithinRedirection()
	{
		$stream = new Stream(fopen('php://memory', 'w'), true, true, true);
		$caught = false;
		$previousLevel = ob_get_level();
		try {
			redirectOutput(
				$stream,
				function () {
					echo 'Hello world';
					throw new \Exception('Test exception');
				}
			);
			$stream->rewind();
			$this->assertEquals(false, true, 'The exception should have been thrown');
		} catch (\Exception $e) {
			# The stream should have been cleand up and the output buffer should have been terminated
			$this->assertEquals($previousLevel, ob_get_level());
			$caught = true;
		}
		finally {
			$this->assertEquals(true, $caught);
		}
	}
}
