<?php namespace spitfire\core\http\request\handler;

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
 * The decorating request handler allows to bind middleware to another
 * request handler, effectively stacking them together.
 * 
 * @see https://www.php-fig.org/psr/psr-15/meta/
 */
class DecoratingRequestHandler implements RequestHandlerInterface
{
	
	/**
	 * The middleware to "wrap" around the request handler, allowing the middleware
	 * to be executed before the underlying handler.
	 * 
	 * @var MiddlewareInterface
	 */
	private $middleware;
	
	/**
	 * The wrapped handler. If the middleware cannot process the request on it's own,
	 * or just provides filtering, this handler will receive the request from the 
	 * middleware.
	 * 
	 * @var RequestHandlerInterface
	 */
	private $handler;
	
	/**
	 * Creates a new decorating request handler, which wraps a requesthandler with a 
	 * middleware so the middleware can intercept the request.
	 * 
	 * @param RequestHandlerInterface $handler
	 * @param MiddlewareInterface $middleware
	 */
	public function __construct(RequestHandlerInterface $handler, MiddlewareInterface $middleware)
	{
		$this->handler = $handler;
		$this->middleware = $middleware;
	}
	
	/**
	 * Use the middleware, the undelying handler, or a combination of both to handle
	 * the request.
	 * 
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return $this->middleware->process($request, $this->handler);
	}
}
