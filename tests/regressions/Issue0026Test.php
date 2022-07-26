<?php namespace tests\spitfire\regressions;

use magic3w\http\url\reflection\URLReflection;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use spitfire\core\Headers;
use spitfire\core\http\request\handler\RouterClosureRequestHandler;
use spitfire\core\Request;
use spitfire\core\Response;
use spitfire\core\router\URIPattern;
use spitfire\io\stream\Stream;

class Issue0026Test extends TestCase
{
	/**
	 * Up until now, when performing a request, the application would mess up the request.
	 * 
	 * @see https://github.com/Csardelacal/SpitfireEngine/issues/26
	 */
	public function testIssue1()
	{
		$route = new URIPattern('/{test}/');
		$handler = new RouterClosureRequestHandler($route, function (string $test) {
			return new Response(Stream::fromString($test));
		});
		
		$request = new Request(
			'GET',
			new URLReflection('https', 'localhost', 443, '/test/', 'hello=world'),
			new Headers(),
			[],
			[],
			Stream::fromString(''),
			[]
		);
		
		$result = $handler->handle($request);
		$this->assertInstanceOf(ResponseInterface::class, $result);
		$this->assertEquals('test', $result->getBody()->getContents());
	}
}
