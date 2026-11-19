<?php namespace tests\spitfire\http\response;

use PHPUnit\Framework\TestCase;
use spitfire\http\Response;
use spitfire\io\stream\Stream;

class EmptyHeaderFatalsTest extends TestCase
{
	
	public function testEmptyHeaderDoesNotFatal()
	{
		$response = new Response(new Stream(fopen('php://temp', 'r'), true, true, false));
		$response = $response->withAddedHeader('X-Test', 'Hello world');
		$this->assertTrue($response->hasHeader('X-Test'));
	}
}
