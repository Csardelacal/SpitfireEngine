<?php namespace spitfire\core\exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use spitfire\contracts\core\LocationsInterface;
use spitfire\core\Response;
use spitfire\io\stream\Stream;
use spitfire\io\template\View;

class ExceptionHandler implements MiddlewareInterface
{
	
	private LocationsInterface $locations;
	
	public function __construct(LocationsInterface $locations)
	{
		$this->locations = $locations;
	}
	
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		try {
			return $handler->handle($request);
		}
		catch (Exception $e) {
			$class = new ReflectionClass($e);
			
			while ($class !== false) {
				$location  = $this->locations->root(
					'app/exceptions/' . str_replace('\\', DIRECTORY_SEPARATOR, $class->getName())
				);
				
				if (file_exists($location)) {
					return new Response(
						Stream::fromString((new View($location, []))->render()),
						500
					);
				}
				
				$class = $class->getParentClass();
			}
			
			return new Response(
				Stream::fromString($e->getTraceAsString()),
				500
			);
		}
	}
}
