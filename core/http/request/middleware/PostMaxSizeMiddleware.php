<?php namespace spitfire\core\http\request\middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\io\Filesize;

class PostMaxSizeMiddleware implements MiddlewareInterface
{
	
	private ResponseInterface $response;
	
	public function __construct(ResponseInterface $response)
	{
		$this->response = $response;
	}
	
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		
		$headers = $request->getHeaders();
		$content_length = reset($headers['content_length']);
		
		/**
		 * Get the server's maximum post message size. This will allow us to determine
		 * whether the original request exceeded maximum size.
		 * 
		 * Generally in this situation the request is worthless, since the server is
		 * receiving a truncated request at best.
		 */
		$max = Filesize::parse(ini_get('post_max_size'));
		
		if ($content_length > $max)
		{
			return $this->response;
		}
		
		return $handler->handle(($request));
	}
}
