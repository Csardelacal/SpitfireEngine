<?php namespace spitfire\mvc\middleware\standard;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\collection\Collection;
use spitfire\ast\Scope;
use spitfire\core\ContextInterface;
use spitfire\core\Response;
use spitfire\provider\Container;
use spitfire\validation\ValidationException;
use spitfire\validation\parser\Parser;
use spitfire\validation\ValidationRule;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class ValidationMiddleware implements MiddlewareInterface
{
	
	/**
	 * 
	 * @var RequestHandlerInterface|null
	 */
	private $response;
	
	/**
	 * 
	 * @var Collection<ValidationRule>
	 */
	private $rules;
	
	/**
	 * The middleware needs access to the container, so that we can interact with
	 * other components of the system appropriately.
	 * 
	 * @var Container
	 */
	private $container;
	
	public function __construct(Container $container, Collection $rules, ?RequestHandlerInterface $errorpage)
	{
		$this->container = $container;
		$this->rules = $rules;
		$this->response = $errorpage;
		
		assert($rules->containsOnly(ValidationRule::class));
	}
	
	/**
	 * Handle the request, performing validation. 
	 * 
	 * @todo If the validation fails, the information should be injected into view, so the application can use it
	 * @todo Introduce a class that maintains the list of validation errors so controllers can locate them
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		$errors = $this->rules->each(function (ValidationRule $rule, string $key) use ($request) {
			return $rule->test($request->getParsedBody()[$key]?? null);
		})->filter();
		
		/**
		 * We invoke the ViewFactory to set the new defaults for views that are created
		 * from here on out. This way, whenever the application invokes the view() method,
		 * we also pass the errors into the new view so the validation can be properly
		 * displayed.
		 */
		$this->container->get(ViewFactory::class)->set('errors', $errors);
		
		/**
		 * If there's errors, and there's a special handler defined for error pages, then we 
		 * send the user to the appropriate page.
		 * 
		 * Here's where I'd recommend introduce a flasher handler that would redirect the user
		 * to the form page and have the data they sent us resubmitted, allowing it to pretend
		 * it is a get request, using a "_method" hidden input.
		 * 
		 * @todo Introduce flasher handler
		 */
		if (!$errors->isEmpty() && $this->response) {
			return $this->response->handle($request);
		}
		
		/**
		 * If the errors are empty, or we just do want the controller to handle them in an explicit
		 * manner, we can let the application do so.
		 */
		return $handler->handle($request);
	}
}
