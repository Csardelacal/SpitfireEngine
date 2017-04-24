<?php namespace tests\spitfire;

use AbsoluteURL;
use PHPUnit\Framework\TestCase;
use spitfire\core\router\Router;
use URL;
use function spitfire;

class URLTest extends TestCase
{
	
	private $setup = false;
	
	public function setUp() {
		\spitfire\core\Environment::get()->set('base_url', '/');
		
		if (!$this->setup) {
			Router::getInstance()->request('/:a/:b', ['controller' => 'test', 'action' => ':b', 'object' => ':a']);
			
			Router::getInstance()->server(':lang.:tld.com')->request('/hello/', ['controller' => 'test', 'action' => 'a', 'object' => 'a']);
			spitfire()->createRoutes();
			
			$this->setup = true;
		}
	}
	
	public function testBlankSerializer() {
		
		$url = new URL();
		$this->assertEquals('/', strval($url));
	}
	
	public function testBlankSerializer2() {
		$url = new URL('home', 'index');
		$this->assertEquals('/', strval($url));
	}
	
	public function testAnotherSerializer() {
		$url = new URL('account', 'test');
		$this->assertEquals('/account/test/', strval($url));
	}
	
	public function testAnotherSerializerWithParams() {
		$url = new URL('account', 'test', ['a' => 3]);
		$this->assertEquals('/account/test/?a=3', strval($url));
	}
	
	public function testArrayReverser() {
		$this->assertEquals('/url/my/',       strval(new URL('test',  'my', 'url')));
		$this->assertEquals('/test2/my/url/', strval(new URL('test2', 'my', 'url')));
	}
	
	public function testServerReverser() {
		$absURL = new AbsoluteURL('test', 'a', 'a');
		$absURL->setDomain(['lang' => 'en', 'tld' => 'test']);
		
		$this->assertEquals('http://en.test.com/hello/', strval($absURL));
	}
	
}