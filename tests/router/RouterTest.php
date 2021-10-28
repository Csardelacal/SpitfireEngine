<?php namespace tests\spitfire\core\router\Route;

/* 
 * This file helps testing the basic functionality of Spitfire's router. It will
 * check that rewriting basic strings and Objects will work properly.
 */

use Closure;
use PHPUnit\Framework\TestCase;
use spitfire\core\router\Route;
use spitfire\core\router\Router;
use tests\router\_support\TestController;

class RouterTest extends TestCase
{
	
	/**
	 * 
	 * @var Router
	 */
	private $router;
	
	public function setUp() : void {
		$this->router = new Router('/');
	}
	
	/**
	 * Tests the creation of routes. This will just request the router to create
	 * a route and verify that the returned value is a Route and not something 
	 * else.
	 */
	public function testCreateRoute() {
		
		$route  = $this->router->get('/test', ['TestController', 'index']);
		$this->assertInstanceOf('\spitfire\core\router\Route', $route);
	}
	
	/**
	 * This method tests the different string rewriting options that Spitfire 
	 * will provide you with when creating routes.
	 */
	public function testStringRoute() {
		
		$router = $this->router;
		
		#Prepare a route that redirects with no parameters
		$route  = $router->get('/test', [TestController::class, 'index']);
		$this->assertEquals(true, $route->test('/test', 'GET', Route::PROTO_HTTP));
		$this->assertEquals(false, $route->test('/test', 'POST', Route::PROTO_HTTP));
			//> This last test should fail because we're sending a POST request to a GET route
		
	}
	
	public function testTrailingSlashStringRoute() {
		$router = new Router('/');
		
		#Create a route with a trailing slash
		$route1 = $router->get('/this/is/a/test/', [TestController::class, 'index']);
		
		$this->assertEquals(true, $route1->test('/this/is/a/test',  'GET', Route::PROTO_HTTP), 'The route should match a route without trailing slash');
		$this->assertEquals(true, $route1->test('/this/is/a/test/', 'GET', Route::PROTO_HTTP), 'The route should match a route with a trailing slash');
		$this->assertEquals(false, $route1->test('/this/is/a/test/with/more', 'GET', Route::PROTO_HTTP), 'The route should not match excessive content');
		
	}
	
	public function testTrailingSlashStringRoute2() 
	{
		$router = new Router('/');
		
		#Create a route without a trailing slash
		$route2 = $router->get('/this/is/a/test', ['TestController', 'index']);
		$this->assertEquals(true, $route2->test('/this/is/a/test/with/more/fragments', 'GET', Route::PROTO_HTTP), 'The route shoud match a route with additional fragments');
		$this->assertEquals(true, $route2->test('/this/is/a/test/', 'GET', Route::PROTO_HTTP), 'The route shoud match a route with a trailing slash');
	}
	
	public function testOptionalParameters() {
		$router = $this->router;
		$router->get('/test/{param1}', [TestController::class, 'index']);
		
		$p1 = $router->rewrite('/test/provided', 'GET', Route::PROTO_HTTP);
		$p2 = $router->rewrite('/test/',         'GET', Route::PROTO_HTTP);
		$p3 = $router->rewrite('/some/',         'GET', Route::PROTO_HTTP);
		
		$this->assertInstanceOf(Closure::class, $p1);
		$this->assertInstanceOf(Closure::class, $p2);
		$this->assertEquals(null, $p3);
	}
	
	public function testMixedURLS() 
	{
		$router  = $this->router;
		$route   = $router->get('/@{param1}', ['UserController', 'index']);
		
		$rewrite = $route->getSource()->test('/@provided');
		$this->assertEquals('provided', $rewrite->getParameter('param1'));
	}
	
	public function testURLReversal() 
	{
		$router  = $this->router;
		$route   = $router->get('/@{param1}', Array('controller' => 'UserController', 'object' => [':param1']));
		$url     = $route->getSource()->reverse(['param1' => 'hello_world']);
		
		$this->assertEquals('/@hello_world', $url);
	}
	
}
