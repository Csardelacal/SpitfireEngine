<?php namespace tests\spitfire\http\request\middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\http\Response;
use spitfire\http\request\middleware\InMemoryStore;
use spitfire\http\request\middleware\RateLimitMiddleware;
use spitfire\http\request\middleware\RateLimitStore;
use spitfire\io\stream\Stream;

/**
 * Unit tests for {@see RateLimitMiddleware}.
 *
 * The middleware only touches ServerRequestInterface (getServerParams, getMethod,
 * getUri) and RequestHandlerInterface::handle(), so it is driven with stubs of
 * those interfaces, a real InMemoryStore, and a real Response for the rejection.
 */
class RateLimitMiddlewareTest extends TestCase
{

	/**
	 * @param RateLimitStore $store
	 * @param int $limit
	 * @param int $window
	 * @param Response|null $rejection
	 * @return RateLimitMiddleware
	 */
	private function middleware(RateLimitStore $store, int $limit, int $window = 60, ?Response $rejection = null): RateLimitMiddleware
	{
		return new RateLimitMiddleware($rejection ?? $this->rejection(), $store, $limit, $window);
	}

	/**
	 * @return Response
	 */
	private function okResponse(): Response
	{
		return new Response($this->emptyBody(), 200, 'OK');
	}

	/**
	 * @return Response
	 */
	private function rejection(): Response
	{
		return new Response($this->emptyBody(), 429, 'Too Many Requests');
	}

	/**
	 * @return Stream
	 */
	private function emptyBody(): Stream
	{
		return new Stream(fopen('php://temp', 'r'), true, true, false);
	}

	/**
	 * A RequestHandlerInterface stub that asserts how often handle() is called.
	 */
	private function handlerExpecting($matcher, ?Response $sentinel = null): RequestHandlerInterface
	{
		$handler = $this->createMock(RequestHandlerInterface::class);
		$call = $handler->expects($matcher)->method('handle');
		if ($sentinel !== null) {
			$call->willReturn($sentinel);
		}
		return $handler;
	}

	/**
	 * @param string $method
	 * @param string $path
	 * @param string $ip
	 * @return ServerRequestInterface
	 */
	private function request(string $method, string $path, string $ip = '203.0.113.9'): ServerRequestInterface
	{
		$uri = $this->createMock(UriInterface::class);
		$uri->method('getPath')->willReturn($path);

		$request = $this->createMock(ServerRequestInterface::class);
		$request->method('getServerParams')->willReturn(['REMOTE_ADDR' => $ip]);
		$request->method('getMethod')->willReturn($method);
		$request->method('getUri')->willReturn($uri);

		return $request;
	}

	public function testImplementsPsr15MiddlewareInterface()
	{
		$this->assertInstanceOf(MiddlewareInterface::class, $this->middleware(new InMemoryStore(), 5));
	}

	public function testPassesThroughWhileUnderTheLimit()
	{
		$limit    = 5;
		$middleware = $this->middleware(new InMemoryStore(), $limit);
		$request  = $this->request('POST', '/api/v1/items');
		$sentinel = $this->okResponse();

		// Five requests stay within the limit and all reach the handler.
		$handler = $this->handlerExpecting($this->exactly($limit), $sentinel);
		for ($i = 0; $i < $limit; $i++)
		{
			$this->assertSame($sentinel, $middleware->process($request, $handler));
		}
	}

	public function testPassesThroughWhenExactlyAtTheLimit()
	{
		// The boundary: the limit-th request is still allowed (the comparison is
		// strictly "greater than", not "greater than or equal").
		$limit = 3;
		$middleware = $this->middleware(new InMemoryStore(), $limit);
		$request  = $this->request('POST', '/api/v1/items');
		$sentinel = $this->okResponse();

		$handler = $this->handlerExpecting($this->exactly($limit), $sentinel);
		for ($i = 0; $i < $limit; $i++)
		{
			$this->assertSame($sentinel, $middleware->process($request, $handler));
		}
	}

	public function testRejectsWhenOverTheLimit()
	{
		$limit      = 2;
		$rejection  = $this->rejection();
		$middleware = $this->middleware(new InMemoryStore(), $limit, 60, $rejection);
		$request    = $this->request('POST', '/api/v1/items');
		$sentinel   = $this->okResponse();

		// Fill the bucket: the first two requests are within the limit.
		$pass = $this->handlerExpecting($this->exactly($limit), $sentinel);
		for ($i = 0; $i < $limit; $i++)
		{
			$middleware->process($request, $pass);
		}

		// The third request exceeds the limit and is rejected; the handler is
		// never called.
		$this->assertSame($rejection, $middleware->process($request, $this->handlerExpecting($this->never(), $sentinel)));
	}

	public function testRejectsOnTheRequestAfterTheLimit()
	{
		$rejection  = $this->rejection();
		$middleware = $this->middleware(new InMemoryStore(), 1, 60, $rejection);
		$request    = $this->request('GET', '/api/v1/items');
		$sentinel   = $this->okResponse();

		// First request: allowed (1 of 1).
		$this->assertSame($sentinel, $middleware->process($request, $this->handlerExpecting($this->once(), $sentinel)));
		// Second request: rejected (2 > 1).
		$this->assertSame($rejection, $middleware->process($request, $this->handlerExpecting($this->never(), $sentinel)));
	}

	public function testUsesIpMethodPathAsTheBucketKey()
	{
		$spy = new class implements RateLimitStore {
			public ?string $lastKey = null;
			public function hit(string $key, int $window): int { $this->lastKey = $key; return 1; }
			public function reset(string $key): void {}
		};

		$middleware = $this->middleware($spy, 5);
		$middleware->process($this->request('POST', '/api/v1/items', '203.0.113.9'), $this->handlerExpecting($this->once(), $this->okResponse()));

		$this->assertSame('203.0.113.9|POST|/api/v1/items', $spy->lastKey);
	}

	public function testTracksClientsSeparatelyByIp()
	{
		$rejection  = $this->rejection();
		$middleware = $this->middleware(new InMemoryStore(), 1, 60, $rejection);
		$sentinel   = $this->okResponse();

		$alice = $this->request('GET', '/api/v1/items', '203.0.113.1');
		$bob   = $this->request('GET', '/api/v1/items', '203.0.113.2');

		// Alice is allowed once...
		$this->assertSame($sentinel, $middleware->process($alice, $this->handlerExpecting($this->once(), $sentinel)));
		// ...then rejected (her bucket is full)...
		$this->assertSame($rejection, $middleware->process($alice, $this->handlerExpecting($this->never(), $sentinel)));
		// ...while Bob, a different client, is still within his own limit.
		$this->assertSame($sentinel, $middleware->process($bob, $this->handlerExpecting($this->once(), $sentinel)));
	}

	public function testProcessReturnsPsr7ResponseInterface()
	{
		$result = $this->middleware(new InMemoryStore(), 5)
			->process($this->request('GET', '/api/v1/items'), $this->handlerExpecting($this->once(), $this->okResponse()));

		$this->assertInstanceOf(ResponseInterface::class, $result);
	}
}
