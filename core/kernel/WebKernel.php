<?php namespace spitfire\core\kernel;

use spitfire\_init\LoadConfiguration;
use spitfire\core\Context;
use spitfire\core\http\request\handler\StaticResponseRequestHandler;
use spitfire\core\http\request\handler\DecoratingRequestHandler;
use spitfire\mvc\RouterMiddleware;
use spitfire\core\Request;
use spitfire\core\Response;
use spitfire\core\router\Router;
use spitfire\exceptions\ExceptionHandler;

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
	
	public function __construct() 
	{
		$this->router = new Router();
	}
	
	public function boot()
	{
	}
	
	public function process(Request $request) : Response
	{
		
		try {
			$notfound = new StaticResponseRequestHandler(new Response('Not found', 404));
			$routed   = new DecoratingRequestHandler(new RouterMiddleware(), $notfound);
			
			return $routed->handle($request);
		}
		catch (\Exception $e) {
			$handler = new ExceptionHandler();
			return $handler->handle($e);
		}
		
		/**
		 * @todo Introduce a decorating request handler that wraps around the router's
		 * middleware and generates a response.
		 */
		$intent = $this->router->rewrite($request);
		
		/*
		 * Sometimes the router can provide a shortcut for really small and simple
		 * responses. It will return a response instead of a Intent, which will cause
		 * the application to just emit the response
		 */
		if ($intent instanceof Response) { return $intent; }
		
		# See PHPFIG PSR15
		# TODO: Router should return a middleware stack
		# TODO: The stack needs to be 'decorated' with requesthandlers
		# TODO: Run the stack
		
		#Start debugging output
		ob_start();

		#If the request has no defined controller, action and object it will define
		#those now.
		$path    = $request->getPath();

		#Receive the initial context for the app. The controller can replace this later
		/*@var $initContext Context*/
		$initContext = ($path instanceof Context)? $path : $request->makeContext();

		#Define the context, include the application's middleware configuration.
		#TODO Replace middleware here with the middleware coming from the router
		#TODO The context object is now starting to feel dated and should be considered being replaced
		current_context($initContext);
		include CONFIG_DIRECTORY . 'middleware.php';

		#Get the return context
		/*@var $context Context*/
		$context = $initContext->run();

		#End debugging output
		$context->view->set('_SF_DEBUG_OUTPUT', ob_get_clean());

		#Send the response
		$context->response->send();
		
		/**
		 * If we follow the spec to a tee, the response contains the headers we need to send,
		 * the data we need to send, etc. We just need a short piece fo code to place those on
		 * PHP's buffers
		 * 
		 * @var Response
		 */
		$response = $request->handle();
		
		/**
		 * First to be sent is the reason phrase. We let PHP choose the status phrase it wishes to
		 * use and just set the status code.
		 */
		http_response_code($response->getStatusCode());
		
		/**
		 * After that, each header is sent to the SAPI so the headers are placed first and the application
		 * behaves consistently. Otherwise we would get a bunch of errors indicating that our headers
		 * already left.
		 * 
		 * The first variable enforces that the header is replaced the first time and appended afterwards,
		 * ensuring that only the headers we set are sent, and all defaults are overridden.
		 */
		foreach($response->getHeaders() as $header => $contents) {
			$first = true;
			
			foreach ($contents as $content) {
				header(sprintf('%s: %s', $header, $content), $first, $response->getStatusCode());
				$first = false;
			}
		}
		
		/**
		 * Print the response body to the output buffer so it gets sent to the end user.
		 */
		echo $response->getBody();
	}
	
	public function router() : Router
	{
		return $this->router;
	}

	public function initScripts(): array 
	{
		return [
			LoadConfiguration::class
		];
	}
	
}
