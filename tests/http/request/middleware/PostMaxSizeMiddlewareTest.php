<?php namespace tests\spitfire\http\request\middleware;

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use spitfire\http\Response;
use spitfire\http\request\middleware\PostMaxSizeMiddleware;
use spitfire\io\stream\Stream;

/**
 * Unit tests for {@see PostMaxSizeMiddleware}.
 *
 * The middleware only touches two PSR contracts — ServerRequestInterface::getHeaders()
 * and RequestHandlerInterface::handle() — so it is driven with stubs of those interfaces,
 * while the rejection object it was constructed with is a real Response.
 *
 * Note: Response::status() whitelists codes and 413 (Payload Too Large) is *not* among them,
 * so the rejection is built with 400 — the closest valid code for an oversized body.
 */
class PostMaxSizeMiddlewareTest extends TestCase
{
	
	const LIMIT_INI = '1K'; // 1024 bytes — pinned small so "exceeds" is unambiguous
	
	protected function setUp(): void
	{
		// Pin the limit so the "exceeds" case is deterministic regardless of the host php.ini.
		ini_set('post_max_size', self::LIMIT_INI);
	}
	
	public function testImplementsPsr15MiddlewareInterface()
	{
		$this->assertInstanceOf(MiddlewareInterface::class, $this->middleware());
	}
	
	public function testPassesThroughWhenContentLengthIsWithinTheLimit()
	{
		$sentinel = $this->okResponse();
		$handler  = $this->handlerExpecting($this->once(), $sentinel);
		
		$result = $this->middleware()->process($this->requestWithContentLength('10'), $handler);
		
		$this->assertSame($sentinel, $result);
	}
	
	public function testPassesThroughWhenContentLengthEqualsTheLimit()
	{
		// The check is strictly `>`, so a body exactly at the limit is allowed through.
		$sentinel = $this->okResponse();
		$handler  = $this->handlerExpecting($this->once(), $sentinel);
		
		$result = $this->middleware()->process($this->requestWithContentLength('1024'), $handler);
		
		$this->assertSame($sentinel, $result);
	}
	
	public function testRejectsWhenContentLengthExceedsTheLimit()
	{
		$rejection = $this->rejection();
		$handler   = $this->handlerExpecting($this->never());
		
		$result = $this->middleware($rejection)->process($this->requestWithContentLength('999999999'), $handler);
		
		$this->assertSame($rejection, $result);
	}
	
	public function testPassesThroughWhenContentLengthHeaderIsAbsent()
	{
		// A request with no content-length (e.g. a GET, or chunked transfer) has no
		// oversized body and must fall through to the handler — not fatal.
		$sentinel = $this->okResponse();
		$handler  = $this->handlerExpecting($this->once(), $sentinel);
		
		$result = $this->middleware()->process($this->requestWithoutContentLength(), $handler);
		
		$this->assertSame($sentinel, $result);
	}
	
	public function testProcessReturnsPsr7ResponseInterface()
	{
		$handler = $this->handlerExpecting($this->once(), $this->okResponse());
		$result  = $this->middleware()->process($this->requestWithContentLength('10'), $handler);
		
		$this->assertInstanceOf(ResponseInterface::class, $result);
	}
	
	public function testExplicitByteLimitRejectsWhenContentLengthExceedsIt()
	{
		$rejection = $this->rejection();
		$handler   = $this->handlerExpecting($this->never());
		
		$result = $this->middlewareWithLimit(1024, $rejection)->process($this->requestWithContentLength('1025'), $handler);
		
		$this->assertSame($rejection, $result);
	}
	
	public function testExplicitByteLimitPassesWhenContentLengthEqualsIt()
	{
		// The check is strictly `>`, so a body exactly at the explicit limit is allowed through.
		$sentinel = $this->okResponse();
		$handler  = $this->handlerExpecting($this->once(), $sentinel);
		
		$result = $this->middlewareWithLimit(1024)->process($this->requestWithContentLength('1024'), $handler);
		
		$this->assertSame($sentinel, $result);
	}
	
	public function testExplicitByteLimitPassesWhenContentLengthIsBelowIt()
	{
		$sentinel = $this->okResponse();
		$handler  = $this->handlerExpecting($this->once(), $sentinel);
		
		$result = $this->middlewareWithLimit(1024)->process($this->requestWithContentLength('10'), $handler);
		
		$this->assertSame($sentinel, $result);
	}
	
	public function testExplicitByteLimitTakesPrecedenceOverIni()
	{
		// An explicit 100-byte cap is far tighter than the server's post_max_size (8M here,
		// which is a protected ini and cannot be lowered at runtime), so a 500-byte body —
		// which the ini limit would allow — must be rejected by the constructor argument.
		$rejection = $this->rejection();
		$handler   = $this->handlerExpecting($this->never());
		
		$result = $this->middlewareWithLimit(100, $rejection)->process($this->requestWithContentLength('500'), $handler);
		
		$this->assertSame($rejection, $result);
	}
	
	// -- helpers ---------------------------------------------------------------
	
	private function middleware(?Response $rejection = null): PostMaxSizeMiddleware
	{
		return new PostMaxSizeMiddleware($rejection ?? $this->rejection());
	}
	
	private function middlewareWithLimit(int $limit, ?Response $rejection = null): PostMaxSizeMiddleware
	{
		return new PostMaxSizeMiddleware($rejection ?? $this->rejection(), $limit);
	}
	
	private function okResponse(): Response
	{
		return new Response($this->emptyBody(), 200, 'OK');
	}
	
	private function rejection(): Response
	{
		return new Response($this->emptyBody(), 413, 'Payload Too Large');
	}
	
	private function emptyBody(): Stream
	{
		return new Stream(fopen('php://temp', 'r'), true, true, false);
	}
	
	private function requestWithContentLength(string $bytes): ServerRequestInterface
	{
		$request = $this->createMock(ServerRequestInterface::class);
		$request->method('getHeader')->with('content-length')->willReturn([$bytes]);
		return $request;
	}
	
	private function requestWithoutContentLength(): ServerRequestInterface
	{
		$request = $this->createMock(ServerRequestInterface::class);
		$request->method('getHeader')->with('content-length')->willReturn(false);
		return $request;
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
}
