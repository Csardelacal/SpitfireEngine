<?php namespace spitfire\core\kernel;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\_init\LoadConfiguration;
use spitfire\_init\ProvidersInit;
use spitfire\_init\ProvidersRegister;
use spitfire\core\http\request\handler\StaticResponseRequestHandler;
use spitfire\core\http\request\handler\DecoratingRequestHandler;
use spitfire\_init\InitRequest;
use spitfire\contracts\core\kernel\WebKernelInterface;
use spitfire\core\Response;
use spitfire\core\router\Router;
use spitfire\core\router\RoutingMiddleware;
use spitfire\io\stream\Stream;

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
class WebKernel implements WebKernelInterface, RequestHandlerInterface
{
	
	private ContainerInterface $container;
	
	public function __construct(ContainerInterface $container)
	{
		$this->container = $container;
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
	 * @todo The router should not be dinamically retrieved. But I'm running into a chicken/egg problem
	 * where the router's service provider needs to be started by the kernel and the kernel needs the
	 * router to determine where it should be sending stuff to.
	 *
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		
		try {
			$notfound = new StaticResponseRequestHandler(new Response(Stream::fromString('Not found'), 404));
			$routed   = new DecoratingRequestHandler($notfound, new RoutingMiddleware($this->container->get(Router::class)));
			
			return $routed->handle($request);
		}
		catch (\Exception $e) {
			$handler = new ExceptionHandler();
			return $handler->handle($e);
		}
	}
	
	public function initScripts(): array
	{
		return [
			LoadConfiguration::class,
			ProvidersRegister::class,
			ProvidersInit::class,
			InitRequest::class
		];
	}
}
