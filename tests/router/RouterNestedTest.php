<?php namespace tests\spitfire\core\router\Route;

/* 
 * This file helps testing the basic functionality of Spitfire's router. It will
 * check that rewriting basic strings and Objects will work properly.
 */

use magic3w\http\url\reflection\URLReflection;
use PHPUnit\Framework\TestCase;
use spitfire\core\Headers;
use spitfire\core\Path;
use spitfire\core\Request;
use spitfire\core\router\Route;
use spitfire\core\router\Router;
use spitfire\core\router\RouterResult;
use spitfire\io\stream\Stream;

class RouterNestedTest extends TestCase
{
	
	/**
	 * 
	 * @var Router
	 */
	private $router;
	
	public function setUp() : void {
		$this->router = new Router('/');
	}
	
	public function testNested() 
	{
		$this->router->scope('/test', function (Router $router) {
			$this->assertEquals('/test', $router->getPrefix());
			$router->request('/hello-world', ['TestController', 'index']);
		});
		
		$request = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/test/hello-world'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString('')
		);
		
		$rewritten = $this->router->rewrite($request);
		$this->assertInstanceOf(RouterResult::class, $rewritten);
	}
	
	public function testMultipleNested() 
	{
		$this->router->scope('/test', function (Router $router) {
			$this->assertEquals('/test', $router->getPrefix());
			
			$router->scope('/test', function (Router $router) {
				$this->assertEquals('/test/test', $router->getPrefix());
				$router->request('/hello-world', ['TestController', 'index']);
			});
		});
		
		$request1 = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/test/test/hello-world'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString('')
		);
		
		$request2 = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/test/hello-world'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString('')
		);
		
		$r1 = $this->router->rewrite($request1);
		$this->assertInstanceOf(RouterResult::class, $r1);
		$this->assertEquals(true, $r1->success());
		
		$r2 = $this->router->rewrite($request2);
		$this->assertEquals(false, $r2->success());
	}
	
}
