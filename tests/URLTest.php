<?php namespace tests\spitfire;

use PHPUnit\Framework\TestCase;
use spitfire\core\config\Configuration;
use spitfire\core\router\Router;
use function spitfire;

class URLTest extends TestCase
{
	
	private $setup = false;
	
	public function setUp() : void 
	{
		
		if (!$this->setup) {
			#Define a configuration for this test
			spitfire()->provider()->set(Configuration::class, new Configuration());
			
			#Set the testing environment
			$config = spitfire()->provider()->get(Configuration::class);
			$config->set('app.hostname', 'localhost');
			
			#Create a route with parameters
			Router::getInstance()->request('/me/:b', ['controller' => 'account', 'action' => ':b']);
			Router::getInstance()->request('/:a/:b', ['controller' => 'test', 'action' => ':b', 'object' => ':a']);
			
			#Create a route for a specific server with parameters
			Router::getInstance()->request('/hello/', ['controller' => 'test', 'action' => 'a', 'object' => 'a']);
			
			#Create a redirection
			Router::getInstance()->request('/static/:page', ['controller' => 'content', 'action' => 'page', 'object' => ':page']);
			
			$this->setup = true;
		}
	}
	
	public function testPrerequisiteBaseURL() {
		$this->assertEquals('/', \spitfire\SpitFire::baseUrl());
	}
	
	public function testBlankSerializer() {
		$url = url();
		$this->assertEquals('/', $url->stringify());
		$this->assertEquals('/', strval($url));
	}
	
	public function testBlankSerializer2() {
		$url = url('home', 'index');
		$this->assertEquals('/', $url->stringify());
		$this->assertEquals('/', strval($url));
	}
	
	public function testAnotherSerializer() {
		$url = url('account', 'test');
		$this->assertEquals('/me/test/', $url->stringify());
		$this->assertEquals('/me/test/', strval($url));
	}
	
	public function testAnotherSerializerWithParams() {
		$url = url('account', 'test', ['a' => 3]);
		$this->assertEquals('/me/test/?a=3', $url->stringify());
		$this->assertEquals('/me/test/?a=3', strval($url));
	}
	
	public function testArrayReverser() {
		$this->assertEquals('/url/my/',       url('test',  'my', 'url')->stringify());
		$this->assertEquals('/url/my/',       strval(url('test',  'my', 'url')));
	}
	
	public function testServerReverser2() {
		$absURL = url('test', 'a', 'a')->absolute();
		$this->assertEquals('https://localhost/a/a/', $absURL->stringify());
		$this->assertEquals('https://localhost/a/a/', strval($absURL));
	}
	
}
