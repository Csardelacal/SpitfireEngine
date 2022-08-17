<?php namespace tests\spitfire\regressions;

use magic3w\http\url\reflection\URLReflection;
use PHPUnit\Framework\TestCase;
use spitfire\core\Headers;
use spitfire\core\Request;
use spitfire\io\stream\Stream;

/**
 * Scrutinizer reported an issue where the Request object would attempt
 * to set the Headers by retrieving their raw data and writing the raw
 * array back instead of the object, which would cause the app to fail.
 */
class Scrut47847257Test extends TestCase
{
	
	public function testSetHeader()
	{
		$request = new Request(
			'GET',
			new URLReflection('https', 'localhost', 443, '/', 'hello=world'),
			new Headers,
			[],
			$_SERVER,
			Stream::fromString(''),
			[]
		);
		
		$request2 = $request->withHeader('test', 'test');
		$this->assertEquals(['test'], $request2->getHeader('test'));
	}
	
	public function testAddToHeader()
	{
		$request = new Request(
			'GET',
			new URLReflection('https', 'localhost', 443, '/', 'hello=world'),
			new Headers,
			[],
			$_SERVER,
			Stream::fromString(''),
			[]
		);
		
		$request2 = $request->withAddedHeader('test', 'test');
		$this->assertEquals(['test'], $request2->getHeader('test'));
	}
	
	public function testUnsetHeader()
	{
		$request = new Request(
			'GET',
			new URLReflection('https', 'localhost', 443, '/', 'hello=world'),
			new Headers,
			[],
			$_SERVER,
			Stream::fromString(''),
			[]
		);
		
		$request2 = $request->withoutHeader('x-Powered-By');
		$this->assertEquals(['Spitfire'], $request->getHeader('x-Powered-By'));
		$this->assertEquals([], $request2->getHeader('x-Powered-By'));
	}
}
