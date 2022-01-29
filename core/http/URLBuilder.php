<?php namespace spitfire\core\http;

use Psr\Http\Message\ServerRequestInterface;
use spitfire\collection\Collection;
use spitfire\core\router\Route;
use spitfire\exceptions\ApplicationException;

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
 * As of Spitfire 2020, the application uses a URL builder, removing the
 * need or ability for customizing URLs that are already built, but making
 * the process of building URL much more simple, streamlined and predictable.
 * 
 * The URL builder generates all URL as strings, making the impact on the
 * system memory slightly smaller.
 */
class URLBuilder
{
	
	/**
	 * The collection of routes this has access to for generating urls from named
	 * resources.
	 * 
	 * @var Collection<Route>
	 */
	private $routes;
	
	/**
	 * The request allows the URLBuilder to generate the current url, and similar
	 * without the need for external input
	 * 
	 * @var ServerRequestInterface
	 */
	private $request;
	
	/**
	 * The location of the application within the server, this is usually '/', but the
	 * application could also be located within a subdirectory like '/myapp/'.
	 * 
	 * The root will get prefixed to all the requests.
	 * 
	 * Due to a quirk in Spitfire, '/' and '' are identical to it.
	 * 
	 * @var string
	 */
	private $root;
	
	/**
	 * The location of the assets within the application. This must be a fully qualified
	 * URL (start with a /, //, http or https). This will get prefixed to all attempts at
	 * generating an asset URL.
	 * 
	 * The root is ignored here.
	 * 
	 * @var string
	 */
	private $assets;
	
	/**
	 * 
	 * @param ServerRequestInterface $request
	 * @param Collection $routes
	 */
	public function __construct(ServerRequestInterface $request, Collection $routes, string $root = '/', string $assets = '/assets/')
	{
		$this->request = $request;
		$this->routes  = $routes;
		$this->assets  = $assets;
		$this->root    = $root;
		
		assert($this->routes->containsOnly(Route::class));
	}
	
	/**
	 * 
	 * @param string|string[] $path
	 * @param string[] $params
	 * @param mixed[] $query
	 */
	public function to($path, $params = [], $query = []) : string
	{
		if (is_array($path)) {
			$path = implode(':', $path);
		}
		
		/**
		 * Check if there is a named route that matches our search. Since
		 * this is the prefered mechanism for creating routes.
		 */
		foreach ($this->routes as $route) {
			if ($route->getName() === $path) {
				return rtrim($this->root, '/') . '/' . ltrim($route->getSource()->reverse($params), '/') . ($query? '?' . http_build_query($query) : '');
			}
		}
		
		/**
		 * If the routes did not match, and the path is a valid URL path,
		 * we will just assume that the developer wanted us to just prefix it
		 * appropriately so the application can find it.
		 * 
		 * This only applies to URL that start with a slash and do not have
		 * a second slash right away.
		 */
		if (isset($path[0]) && $path[0] === '/' && isset($path[1]) && $path[1] !== '/') {
			return rtrim($this->root, '/') . '/' . ltrim($path, '/') . ($params? '?' . http_build_query($params) : ''); 
		}
		
		/**
		 * We assume that the developer knows what they're doing, and that the
		 * URL was valid anyway
		 */
		return $path . ($params? '?' . http_build_query($params) : ''); 
	}
	
	public function current() : string
	{
		$path    = $this->request->getUri()->getPath();
		$get     = $this->request->getQueryParams();
		
		return sprintf('%s?%s', $path, http_build_query($get));
	}
	
	public function asset(string $path) : string
	{
		return sprintf('%s/%s', rtrim($this->assets, '/'), trim($path, '/'));
	}
	
	
	public function hostname() : string
	{
		
		/**
		 * If the application has defined a hostname for itself, the URL generator
		 * should respect this and use the URL provided.
		 */
		if (config('app.hostname', false)) {
			return config('app.hostname');
		}
		
		/**
		 * If the application provides no hostname for itself, the web-server's hostname
		 * will be used. Please note that there's a few security considerations when 
		 * working with this, so it's recommended to set a canonical hostname in config.
		 */
		if ($_SERVER['SERVER_NAME']?? false) {
			return $_SERVER['SERVER_NAME'];
		}
		
		throw new ApplicationException('Could not detemine the hostname for an absolute url', 2104181221);
	}
}
