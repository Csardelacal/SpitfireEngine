<?php namespace tests\spitfire\core\router\Route;

/* 
 * This file helps testing the basic functionality of Spitfire's router. It will
 * check that rewriting basic strings and Objects will work properly.
 */

use PHPUnit\Framework\TestCase;
use spitfire\core\Path;
use spitfire\core\router\Route;
use spitfire\core\router\Router;
use spitfire\core\router\RouterResult;

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
		
		$rewritten = $this->router->rewrite('/test/hello-world', 'GET', Route::PROTO_HTTP);
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
		
		$r1 = $this->router->rewrite('/test/test/hello-world', 'GET', Route::PROTO_HTTP);
		$this->assertInstanceOf(Path::class, $r1);
		
		$r1 = $this->router->rewrite('/test/hello-world', 'GET', Route::PROTO_HTTP);
		$this->assertEquals(false, $r1);
	}
	
}
