<?php namespace spitfire\core\router;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

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
 * The routing middleware is one of the central components of Spitfire,
 * it allows the application to receive the request, and produce a response
 * based on the routes that the developers designed for the application.
 * 
 * This middleware is instanced by the webkernel, together with the router
 * that it needs in order to locate the appropriate controller.
 */
class RoutingMiddleware implements MiddlewareInterface
{
	
	/**
	 * This router will be used to generate a response. Please note that we instance
	 * it and do not use any gloabl functions to retrieve it. This provides us with a 
	 * way more powerful mechanism for testing and reduces the cohesion of the components
	 * significantly.
	 * 
	 * @var Router
	 */
	private $router;
	
	public function __construct(Router $router)
	{
		$this->router = $router;
	}
	
	/**
	 * This routing middleware is based on the work of the PHP-FIG PSR15 working group,
	 * it asks the router to query the current request for a matching route, and if it
	 * is found. If this is the case, we generate a response from the route.
	 * 
	 * @see https://www.php-fig.org/psr/psr-15/meta/
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		/**
		 * The router is expected to return a result object that we are going to query whether
		 * the request can be handled by the router, and if so, how.
		 */
		$result = $this->router->rewrite($request);
		
		/**
		 * If the router returned a successful match, we will use that match to handle the request.
		 */
		if ($result->success()) {
			return $result->getHandler()->handle($request);
		}
		
		/**
		 * If the router cannot handle the request, because there's no routes available that match the
		 * request, we issue the next handler's response. This will usually be a 404 page, or similar.
		 */
		return $handler->handle($request);
	}
}
