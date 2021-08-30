<?php namespace spitfire\core\kernel;

use spitfire\_init\LoadConfiguration;
use spitfire\_init\ProvidersInit;
use spitfire\_init\ProvidersRegister;
use spitfire\core\http\request\handler\StaticResponseRequestHandler;
use spitfire\core\http\request\handler\DecoratingRequestHandler;
use spifire\io\Stream;
use spitfire\core\Request;
use spitfire\core\Response;
use spitfire\core\router\Router;
use spitfire\core\router\RoutingMiddleware;
use spitfire\exceptions\ExceptionHandler;
use spitfire\provider\Container;

/* 
 * The MIT License
 *
 * Copyright 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The web kernel allows the application to interact with a web server and to 
 * select a controller that will provide an adequate response to the request.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class WebKernel implements KernelInterface
{
	
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
			$this->router = $provider->assemble(Router::class, ['prefix' => '']);
			$provider->set(Router::class, $this->router);
		}
	}
	
	public function boot()
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
			ProvidersInit::class
		];
	}
	
}
