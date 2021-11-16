<?php namespace spitfire\core\http\request\handler;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionMethod;
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
 * The router action handler allows the application to map a route to an action
 * within a controller. This generates a request handler that will use the route
 * to extract parameters and appropriately invoke the action.
 * 
 * For example, when building a route, the application is unable to determine how
 * the route's parameters will be resolved. The route /user/{id} will be mapped to
 * the UserController::retrieve action that receives the id as a parameter.
 * 
 * This means that for a request to /user/1 the route is created without knowledge
 * of the requested url, but the router must return a request handler capable of 
 * handling the URL /user/1
 * 
 * This leaves two options, returning a request handler that is able to reuse the route
 * to retrieve the parameters it needs, or creating a single use request handler that
 * ignores the request it receives. We're choosing the first approach for this.
 */
class RouterActionRequestHandler implements RequestHandlerInterface
{
	
	/**
	 * 
	 * @var URIPattern
	 */
	private $route;
	
	private $controller;
	
	private $action;
	
	public function __construct(URIPattern $route, string $controller, string $action)
	{
		$this->route = $route;
		$this->controller = $controller;
		$this->action = $action;
	}
	
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		$parameters = $this->route->test($request->getUri());
		
		$controller = new $this->controller;
		$action     = $this->action;
		
		/**
		 * The router has a special ability, allowing it to convert strings into models
		 * when the method of the controller expects it to.
		 */
		$reflection = new ReflectionMethod($controller, $action);
		$params     = ReflectionParameterTransformer::transformParameters($reflection, $parameters->getParameters());
		
		$response   = spitfire()->provider()->callMethod($controller, $action, $params);
		
		if ($response instanceof ResponseInterface) {
			return $response;
		}
		
		throw new ApplicationException('Invalid controller response');
	}
}
