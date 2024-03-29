<?php namespace spitfire\core\router;

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

use Closure;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\core\http\request\handler\DecoratingRequestHandler;
use spitfire\exceptions\ApplicationException;

/**
 * Routers are tools that allow your application to listen on alternative urls and
 * attach controllers to different URLs than they would normally do. Please note
 * that enabling a route to a certain controller does not disable it's canonical
 * URL.
 *
 * @author César de la Cal <cesar@magic3w.com>
 */
class Router extends Routable
{
	
	/**
	 * The middleware this router applies to it's routes and children. Middleware is
	 * applied once the Intent object is created and being returned.
	 *
	 * This means that more specific middleware is applied first when handling a request,
	 * and later when handling a response.
	 *
	 * @var Collection<MiddlewareInterface>
	 */
	private $middleware;
	
	/**
	 * These routers inherit from this. Whenever this router is tasked with handling a
	 * request that it cannot satisfy itself, the router will delegate this request to
	 * it's children.
	 *
	 * This behavior implies that routes defined in the parent take precedence over the
	 * routes defined by it's children.
	 *
	 * Also note: whenever you call the scope() method, the router generates a NEW Router
	 * for your scope. This means that you can have routers that manage routes within
	 * the same scope but have different middleware.
	 *
	 * @var Collection<Router>
	 */
	private $children;
	
	/**
	 * Initialize the router. Please note that a router is always scoped to a namespace.
	 * By default, this will be the global scope.
	 *
	 * @param string $prefix
	 */
	public function __construct(string $prefix = '/')
	{
		$this->middleware = new TypedCollection(MiddlewareInterface::class);
		$this->children = new TypedCollection(Router::class);
		parent::__construct($prefix);
	}
	
	public function middleware(MiddlewareInterface $middleware) : Router
	{
		$this->middleware->push($middleware);
		return $this;
	}
	
	/**
	 * This rewrites a request into a Path (or in given cases, a Response). This
	 * allows Spitfire to use the data from the Router to accordingly find a
	 * controller to handle the request being thrown at it.
	 *
	 * Please note that Spitfire is 'lazy' about it's routes. Once it found a valid
	 * one that can be used to respond to the request it will stop looking for
	 * another possible rewrite.
	 *
	 * @throws ApplicationException
	 * @param ServerRequestInterface $request
	 * @return RouterResult
	 */
	public function rewrite(ServerRequestInterface $request) : RouterResult
	{
		
		#Combine routes from the router and server
		$routes = parent::getRoutes()->toArray();
		
		#Test the routes
		foreach ($routes as $route) { /*@var $route Route*/
			#All routes must obviously be a instance of Route
			assert($route instanceof Route);
			
			#Verify whether the route is valid at all
			if (!$route->test($request)) {
				continue;
			}
			
			/**
			 * The middleware is placed around the rewritten route in a
			 * decorated stack of middleware.
			 */
			return new RouterResult($this->middleware->reverse()->reduce(function (RequestHandlerInterface $handler, MiddlewareInterface $middleware) {
				return new DecoratingRequestHandler($handler, $middleware);
			}, $route->getTarget()));
		}
		
		/**
		 * In case the router could not handle the route itself, iterate over the children.
		 *
		 * If any of the children is able to issue a request handler for this, we should
		 * return it.
		 *
		 * By doing it this way, children routes have lower precedence than the parent, meaning
		 * that a parent route that matches a request will override a child.
		 */
		foreach ($this->children as $child) {
			$_r = $child->rewrite($request);
			assert($_r instanceof RouterResult);
			
			if ($_r->success()) {
				
				/**
				 * If the router is processing the result from an underlying router, it will simply
				 * unwrap it's result and decorate it with middleware before returning it as a new
				 * result.
				 */
				return new RouterResult($this->middleware->reverse()->reduce(function (RequestHandlerInterface $handler, MiddlewareInterface $middleware) : RequestHandlerInterface {
					return new DecoratingRequestHandler($handler, $middleware);
				}, $_r->getHandler()));
			}
		}
		
		#Implicit else.
		return new RouterResult(null);
	}
	
	/**
	 *
	 *
	 * @param string $scope
	 * @param Closure $do
	 * @return Router
	 */
	public function scope(string $scope, Closure $do = null) : Router
	{
		$child = new Router(rtrim($this->getPrefix(), '/') . '/' . ltrim($scope, '/'));
		$do && $do($child);
		
		$this->children->push($child);
		
		return $child;
	}
	
	/**
	 * Finds a route by name, this searches recursively, so you don't need to.
	 * Since this method searches for the route, it may become taxing to your
	 * application if you have a ton of routes.
	 *
	 * @param string $name
	 * @return Route|null
	 */
	public function findByName($name): ?Route
	{
		
		/**
		 * First, search this router for any routes that match the given name
		 */
		foreach ($this->getRoutes() as $route) {
			/**
			 * The router must only contain instances of route, if this is not the
			 * case, we do have a problem.
			 */
			assert($route instanceof Route);
			
			if ($route->getName() === $name) {
				return $route;
			}
		}
		
		/**
		 * If they're not in this router, make sure that the child routers do not
		 * have it either.
		 */
		foreach ($this->children as $child) {
			/**
			 * Again, children only contain instances of router, if this is not the
			 * case, we do have a problem.
			 */
			assert($child instanceof Router);
			
			/**
			 * If the child does find a matching route, we stop there.
			 */
			$result = $child->findByName($name);
			if ($result) {
				return $result;
			}
		}
		
		
		/**
		 * Last case would be if the router did not have any matching route. In this case,
		 * we return null to indicate that there is no such route available.
		 */
		return null;
	}
	
	/**
	 * Returns all the routes this router and it's descendants contains.
	 * 
	 * @return Collection<Route>
	 */
	public function getRoutes() : Collection
	{
		$routes = parent::getRoutes();
		$this->children->each(fn(Router $e) => $routes->add($e->getRoutes()));
		
		return $routes;
	}
}
