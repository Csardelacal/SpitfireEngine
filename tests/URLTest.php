<?php namespace tests\spitfire;

use URL;
use PHPUnit\Framework\TestCase;

class URLTest extends TestCase
{
	
	public function testBlankSerializer() {
		
		\spitfire\core\router\Router::getInstance()->request('/:a/:b', ['controller' => 'test', 'action' => ':b', 'object' => ':a']);
		spitfire()->createRoutes();
		
		$url = new URL();
		$this->assertEquals('/', strval($url));
	}
	
	public function testBlankSerializer2() {
		$url = new URL('home', 'index');
		$this->assertEquals('/', strval($url));
	}
	
	public function testAnotherSerializer() {
		$url = new URL('account', 'test');
		$this->assertEquals('/account/test', strval($url));
	}
	
	public function testAnotherSerializerWithParams() {
		$url = new URL('account', 'test', ['a' => 3]);
		$this->assertEquals('/account/test?a=3', strval($url));
	}
	
	public function testArrayReverser() {
		$this->assertEquals('/url/my',       strval(new URL('test',  'my', 'url')));
		$this->assertEquals('/test2/my/url', strval(new URL('test2', 'my', 'url')));
	}
	
}