<?php namespace tests\spitfire\core\router\Route;

/* 
 * This file helps testing the basic functionality of Spitfire's router. It will
 * check that rewriting basic strings and Objects will work properly.
 */

use Closure;
use magic3w\http\url\reflection\URLReflection;
use PHPUnit\Framework\TestCase;
use spitfire\core\Headers;
use spitfire\core\Request;
use spitfire\core\router\Route;
use spitfire\core\router\Router;
use spitfire\core\router\RouterResult;
use spitfire\io\stream\Stream;
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
		$request = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/test/test/hello-world'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString('')
		);
		
		#Prepare a route that redirects with no parameters
		$route  = $router->get('/test', [TestController::class, 'index']);
		$this->assertEquals(true, $route->test($request));
		$this->assertEquals(false, $route->test($request->withMethod('POST')));
			//> This last test should fail because we're sending a POST request to a GET route
		
	}
	
	public function testTrailingSlashStringRoute() {
		$router = new Router('/');
		$request = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/this/is/a/test'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString('')
		);
		
		#Create a route with a trailing slash
		$route1 = $router->get('/this/is/a/test/', [TestController::class, 'index']);
		
		$this->assertEquals(true, $route1->test($request), 'The route should match a route without trailing slash');
		$this->assertEquals(true, $route1->test($request->withUri(URLReflection::fromURL('https://localhost/this/is/a/test/'))), 'The route should match a route with a trailing slash');
		$this->assertEquals(false, $route1->test($request->withUri(URLReflection::fromURL('https://localhost/this/is/a/test/with/more'))), 'The route should not match excessive content');
		
	}
	
	public function testTrailingSlashStringRoute2() 
	{
		$router = new Router('/');
		$request = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/this/is/a/test'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString('')
		);
		
		#Create a route without a trailing slash
		$route2 = $router->get('/this/is/a/test', ['TestController', 'index']);
		$this->assertEquals(true, $route2->test($request->withUri(URLReflection::fromURL('https://localhost/this/is/a/test/with/more'))), 'The route should match a route with additional fragments');
		$this->assertEquals(true, $route2->test($request), 'The route shoud match a route with a trailing slash');
	}
	
	public function testOptionalParameters() {
		$router = $this->router;
		$router->get('/test/{param1}', [TestController::class, 'index']);
		
		$request = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/this/is/a/test'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString('')
		);
		
		$p1 = $router->rewrite($request->withUri(URLReflection::fromURL('https://localhost/test/provided')));
		$p2 = $router->rewrite($request->withUri(URLReflection::fromURL('https://localhost/test')));
		$p3 = $router->rewrite($request->withUri(URLReflection::fromURL('https://localhost/some')));
		
		$this->assertInstanceOf(RouterResult::class, $p1);
		$this->assertInstanceOf(RouterResult::class, $p2);
		$this->assertEquals(false, $p3->success());
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
