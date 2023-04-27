<?php namespace tests\spitfire\core\router\Route;
/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */


/* 
 * This file helps testing the basic functionality of Spitfire's router. It will
 * check that rewriting basic strings and Objects will work properly.
 */

use magic3w\http\url\reflection\URLReflection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
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
	private Router $router;
	
	public function setUp() : void
	{
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
			Stream::fromString(''),
			[]
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
			Stream::fromString(''),
			[]
		);
		
		$request2 = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/test/hello-world'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString(''),
			[]
		);
		
		$r1 = $this->router->rewrite($request1);
		$this->assertInstanceOf(RouterResult::class, $r1);
		$this->assertEquals(true, $r1->success());
		
		$r2 = $this->router->rewrite($request2);
		$this->assertEquals(false, $r2->success());
	}
	
	/**
	 * When a router is asked about the routes it contains, the 
	 * router should always include the routes of it's children.
	 */
	public function testRouterCollectsNestedRoutes()
	{
		
		$this->router->get('/hello-world', ['TestController', 'index']);
		$this->assertEquals(1, $this->router->getRoutes()->count());
		
		$this->router->scope('/test', function (Router $router) {
			$router->request('/hello-world', ['TestController', 'index']);
		});
		
		$this->assertEquals(2, $this->router->getRoutes()->count());
	}
	
	/**
	 * The router should defer testing of the routes to it's children. Sometimes
	 * the router will get greedy and test the children's routes, which should not
	 * happen.
	 */
	public function testRouterDoesNotStealFromItsChildren() 
	{
		$this->router->scope('/test/test', function (Router $router) {
			$router->request('/hello-world', fn() => response(Stream::fromString('Bad')));
			
			/**
			 * If the router stole the request from it's child, the middleware will be
			 * missing from the handler and it will not work appropriately.
			 */
			$router->middleware(new class implements MiddlewareInterface {
				public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
				{
					return response(Stream::fromString('Good'));
				}
			});
		});
		
		$request1 = new Request(
			'GET', 
			URLReflection::fromURL('https://localhost/test/test/hello-world'), 
			new Headers(), 
			[], 
			[], 
			Stream::fromString(''),
			[]
		);
		
		$r1 = $this->router->rewrite($request1);
		$this->assertInstanceOf(RouterResult::class, $r1);
		$this->assertEquals(true, $r1->success());
		
		$response = $r1->getHandler()->handle($request1);
		$this->assertEquals('Good', $response->getBody()->getContents());
	}
}
