<?php namespace tests\spitfire\core\router\Route;

/* 
 * This file helps testing the basic functionality of Spitfire's router. It will
 * check that rewriting basic strings and Objects will work properly.
 */

use PHPUnit\Framework\TestCase;
use spitfire\core\Path;
use spitfire\core\router\Parameters;
use spitfire\core\router\Route;
use spitfire\core\router\RouteMismatchException;
use spitfire\core\router\Router;

class RouterTest extends TestCase
{
	
	private $router;
	
	public function setUp() : void {
		$this->router = new Router();
	}
	
	/**
	 * Tests the creation of routes. This will just request the router to create
	 * a route and verify that the returned value is a Route and not something 
	 * else.
	 */
	public function testCreateRoute() {
		
		$route  = $this->router->get('/test', ['controller' => 'test']);
		$this->assertInstanceOf('\spitfire\core\router\Route', $route);
	}
	
	/**
	 * This method tests the different string rewriting options that Spitfire 
	 * will provide you with when creating routes.
	 */
	public function testStringRoute() {
		
		$router = $this->router;
		
		#Prepare a route that redirects with no parameters
		$route  = $router->get('/test', ['controller' => 'test']);
		$this->assertEquals(true, $route->test('/test', 'GET', Route::PROTO_HTTP, $router->server()));
		$this->assertInstanceOf(Path::class, $route->rewrite('/test', 'GET', Route::PROTO_HTTP, new Parameters));
		$this->assertEquals(false, $route->test('/test', 'POST', Route::PROTO_HTTP, $router->server()));
			//> This last test should fail because we're sending a POST request to a GET route
		
	}
	
	public function testTrailingSlashStringRoute() {
		$router = $this->router;
		
		#Create a route with a trailing slash
		$route1 = $router->get('/this/is/a/test/', ['controller' => 'test']);
		
		$this->assertEquals(true, $route1->test('/this/is/a/test',  'GET', Route::PROTO_HTTP, $router->server()), 'The route should match a route without trailing slash');
		$this->assertEquals(true, $route1->test('/this/is/a/test/', 'GET', Route::PROTO_HTTP, $router->server()), 'The route should match a route with a trailing slash');
		$this->assertEquals(false, $route1->test('/this/is/a/test/with/more', 'GET', Route::PROTO_HTTP, $router->server()), 'The route shouldnot match excessive content');
		
		$this->assertInstanceOf(Path::class, $route1->rewrite('/this/is/a/test/', 'GET', Route::PROTO_HTTP, new Parameters), 'The route should match a route with a trailing slash');
		
		$this->expectException(RouteMismatchException::class, 'The route should not match additional pieces');
		$route1->rewrite('/this/is/a/test/with/extra', 'GET', Route::PROTO_HTTP, new Parameters);
	}
	
	public function testTrailingSlashStringRoute2() {
		$router = $this->router;
		
		#Create a route without a trailing slash
		$route2 = $router->get('/this/is/a/test', ['controller' => 'test']);
		$this->assertEquals(true, $route2->test('/this/is/a/test/with/more/fragments', 'GET', Route::PROTO_HTTP, $router->server()), 'The route shoud match a route with additional fragments');
		$this->assertEquals(true, $route2->test('/this/is/a/test/', 'GET', Route::PROTO_HTTP, $router->server()), 'The route shoud match a route with a trailing slash');
	}
	
	public function testArrayRoute() {
		$router = $this->router;
		
		#Rewrite a parameter based URL into an array
		$route = $router->get('/:param1/:param2', Array('controller' => ':param1', 'action' => ':param2'));
		
		#Test whether matching works for the array string
		$this->assertEquals(true, $route->test('/another/test', 'GET', Route::PROTO_HTTP, $router->server()));
		
		#Test if the route returns a Path object
		$this->assertInstanceOf('\spitfire\core\Path', $route->rewrite('/another/test', 'GET', Route::PROTO_HTTP, $router->server()->test('localhost')));
		#Test if the server returns a Patch object
		$this->assertInstanceOf('\spitfire\core\Path', $router->server()->rewrite('localhost', '/another/test', 'GET', Route::PROTO_HTTP));
		
		#Test if the rewriting succeeded and the data was written in the right spot
		$path  = $router->rewrite('localhost', '/another/test', 'GET', Route::PROTO_HTTP);
		$this->assertEquals('another', current($path->getController()));
		$this->assertEquals('test',    $path->getAction());
	}
	
	public function testArrayRouteWithStaticFragments() {
		$router = $this->router;
		
		#Rewrite a parameter based URL into an array
		$router->get('/:param1/:param2', Array('controller' => ':param1', 'action' => 'something', 'object' => ':param2'));
		
		#Test if the rewriting succeeded and the data was written in the right spot
		$path  = $router->rewrite('localhost', '/another/test', 'GET', Route::PROTO_HTTP);
		$this->assertEquals('another',     current($path->getController()));
		$this->assertEquals('something',   $path->getAction());
		$this->assertEquals(Array('test'), $path->getObject());
	}
	
	public function testOptionalParameters() {
		$router = $this->router;
		$router->get('/test/:param1?optional', Array('controller' => ':param1'));
		
		$p1 = $router->rewrite('localhost', '/test/provided', 'GET', Route::PROTO_HTTP);
		$p2 = $router->rewrite('localhost', '/test/',         'GET', Route::PROTO_HTTP);
		$p3 = $router->rewrite('localhost', '/some/',         'GET', Route::PROTO_HTTP);
		
		$this->assertEquals('provided', current($p1->getController()));
		$this->assertEquals('optional', current($p2->getController()));
		
		$this->assertEquals(false, $p3);
	}
	
	public function testExtension() {
		$router = $this->router;
		$router->get('/test/:param1', Array('controller' => ':param1'));
		
		$p1 = $router->rewrite('localhost', '/test/provided.xml',   'GET', Route::PROTO_HTTP);
		$p2 = $router->rewrite('localhost', '/test/provided.json',  'GET', Route::PROTO_HTTP);
		$p3 = $router->rewrite('localhost', '/test/provided.json/', 'GET', Route::PROTO_HTTP);
		
		$this->assertEquals('xml',  $p1->getFormat());
		$this->assertEquals('json', $p2->getFormat());
		$this->assertEquals('php',  $p3->getFormat());
	}
	
	public function testExtraction() {
		$router  = $this->router;
		$reverse = \spitfire\core\router\ParametrizedPath::fromArray(['controller' => ':p']);
		$router->get('/test/:param1', Array('controller' => ':param1'));
		
		$rewrite = $router->rewrite('localhost', '/test/provided', 'GET', Route::PROTO_HTTP);
		$data = $reverse->extract($rewrite);
		$this->assertEquals('provided', $data->getParameter('p'));
	}
	
}
