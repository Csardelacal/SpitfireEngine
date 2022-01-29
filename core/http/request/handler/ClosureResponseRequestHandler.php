<?php namespace spitfire\core\http\request\handler;

use Closure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The closure request handler is basically a message, it contains a closure and a 
 * set of parameters to pass to the closure so it can be executed and the response
 * retrieved from the result.
 * 
 * Please note that the return value of the closure is not checked, if it's not a 
 * ResponseInterface it will fail.
 */
class ClosureResponseRequestHandler implements RequestHandlerInterface
{
	
	/**
	 *
	 * @var ResponseInterface
	 */
	private $response;
	
	/**
	 * @var mixed[]
	 */
	private $params;
	
	public function __construct(Closure $response, array $params)
	{
		$this->response = $response;
		$this->params = $params;
	}
	
	/**
	 * 
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		/**
		 * Just splatter the parameters we received with the closure into the 
		 * closure and execute it.
		 */
		return ($this->response)(...$this->params);
	}
}
