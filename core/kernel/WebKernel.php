<?php namespace spitfire\core\kernel;

use spitfire\_init\LoadConfiguration;
use spitfire\_init\ProvidersInit;
use spitfire\_init\LoadCluster;
use spitfire\_init\ProvidersFromManifest;
use spitfire\_init\ProvidersRegister;
use spitfire\core\http\request\handler\StaticResponseRequestHandler;
use spitfire\core\http\request\handler\DecoratingRequestHandler;
use spifire\io\Stream;
use spitfire\_init\InitRequest;
use spitfire\core\Request;
use spitfire\core\Response;
use spitfire\core\router\Router;
use spitfire\core\router\RoutingMiddleware;
use spitfire\exceptions\ExceptionHandler;
use spitfire\provider\Container;

/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-1301  USA
 */

/**
 * The web kernel allows the application to interact with a web server and to 
 * select a controller that will provide an adequate response to the request.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class WebKernel implements KernelInterface
{
	
	/**
	 * 
	 * @var Router
	 */
	private $router;
	
	public function __construct(Container $provider) 
	{
		/**
		 * If a router has already been defined for the system to use from here on
		 * out, we will do so.
		 */
		if ($provider->has(Router::class)) {
			$this->router = $provider->get(Router::class);
		}
		
		/**
		 * Otherwise we will have the provider assemble one so we can use it the way
		 * we prefer.
		 */
		else {
			$router = $provider->assemble(Router::class, ['prefix' => '']);
			assert($router instanceof Router);
			$this->router = $router;
			$provider->set(Router::class, $this->router);
		}
	}
	
	public function boot() : void
	{
	}
	
	/**
	 * The web kernel receives a request and processes it to generate a response. At the time
	 * of writing this means that Spitfire will use the router to find a compatible controller,
	 * and if this didn't work, it will proceed to issue a standard 404 page.
	 * 
	 * If the application ran into a different error than not having a route available, Spitfire
	 * will issue an appropriate error page.
	 * 
	 * @param Request $request
	 * @return Response
	 */
	public function process(Request $request) : Response
	{
		
		try {
			$notfound = new StaticResponseRequestHandler(new Response(new Stream('Not found'), 404));
			$routed   = new DecoratingRequestHandler(new RoutingMiddleware($this->router), $notfound);
			
			return $routed->handle($request);
		}
		catch (\Exception $e) {
			$handler = new ExceptionHandler();
			return $handler->handle($e);
		}
	}
	
	public function router() : Router
	{
		return $this->router;
	}
	
	public function initScripts(): array 
	{
		return [
			LoadConfiguration::class,
			ProvidersRegister::class,
			ProvidersFromManifest::class,
			ProvidersInit::class,
			LoadCluster::class,
			InitRequest::class
		];
	}
}
