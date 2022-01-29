<?php namespace spitfire\core\http\request\handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\core\Response;

class StaticResponseRequestHandler implements RequestHandlerInterface
{
	
	/**
	 *
	 * @var ResponseInterface
	 */
	private $response;
	
	/**
	 * 
	 * @param Response $response
	 */
	public function __construct(Response $response)
	{
		$this->response = $response;
	}
	
	/**
	 * 
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request): ResponseInterface
	{
		return $this->response;
	}
}
