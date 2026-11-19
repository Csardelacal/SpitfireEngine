<?php namespace spitfire\http\request\middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Rejects a request once the caller has made more than the allowed number of
 * requests within the window. Both the rejection Response (typically 429 Too
 * Many Requests) and the backing {@see RateLimitStore} are injected, so the
 * consuming application decides both the response it returns and where the
 * counts live.
 */
class RateLimitMiddleware implements MiddlewareInterface
{

	private ResponseInterface $response;
	private RateLimitStore $store;
	private int $limit;
	private int $window;

	/**
	 * @param ResponseInterface $response Returned (short-circuit) when the limit is exceeded.
	 * @param RateLimitStore $store Where the hit counts live.
	 * @param int $limit Maximum number of requests per window before rejection.
	 * @param int $window The window, in seconds.
	 */
	public function __construct(ResponseInterface $response, RateLimitStore $store, int $limit, int $window)
	{
		$this->response = $response;
		$this->store    = $store;
		$this->limit    = $limit;
		$this->window   = $window;
	}

	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		if ($this->store->hit($this->key($request), $this->window) > $this->limit)
		{
			return $this->response;
		}

		return $handler->handle($request);
	}

	/**
	 * Derives the rate-limit bucket from the request: IP + method + path.
	 *
	 * Kept protected (rather than a separate Keyer interface) so an application
	 * that needs a different strategy — per-user, per-endpoint, per-plan — can
	 * subclass and override a single method instead of forking the middleware.
	 *
	 * @param ServerRequestInterface $request
	 * @return string
	 */
	protected function key(ServerRequestInterface $request): string
	{
		$ip   = $request->getServerParams()['REMOTE_ADDR'] ?: 'anonymous';
		$path = $request->getUri()->getPath();

		return implode('|', array($ip, $request->getMethod(), $path));
	}
}
