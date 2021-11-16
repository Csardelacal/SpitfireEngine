<?php namespace spitfire\core\http\request\handler;

use Closure;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionFunction;
use spitfire\core\Response;
use spitfire\core\router\Route;
use spitfire\core\router\URIPattern;
use spitfire\exceptions\ApplicationException;
use spitfire\model\support\ReflectionParameterTransformer;

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
 * 
 */
class RouterClosureRequestHandler implements RequestHandlerInterface
{
	
	/**
	 * 
	 * @var URIPattern
	 */
	private $route;
	
	private $closure;
	
	
	public function __construct(URIPattern $route, Closure $closure)
	{
		$this->route = $route;
		$this->closure = $closure;
	}
	
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$parameters = $this->route->test($request->getUri());
		
		/**
		 * The router has a special ability, allowing it to convert strings into models
		 * when the method of the controller expects it to.
		 */
		$reflection = new ReflectionFunction($this->closure);
		$params     = ReflectionParameterTransformer::transformParameters($reflection, $parameters->getParameters());
		
		/**
		 * Now we can pass this onto the provider to resolve the remaining types
		 */
		$response   = spitfire()->provider()->call($this->closure, $params);
		
		if ($response instanceof ResponseInterface) {
			return $response;
		}
		
		throw new ApplicationException('Invalid controller response');
	}
}
