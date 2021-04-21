<?php namespace spitfire\core\http\request\handler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class StaticResponseRequestHandler implements RequestHandlerInterface
{
	
	/**
	 *
	 * @var ResponseInterface
	 */
	private $response;
	
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