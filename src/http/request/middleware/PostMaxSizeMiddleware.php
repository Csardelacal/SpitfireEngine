<?php namespace spitfire\http\request\middleware;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PostMaxSizeMiddleware implements MiddlewareInterface
{
	
	private ResponseInterface $response;
	
	private int $limit;
	
	public function __construct(ResponseInterface $response, ?int $limit = null)
	{
		$this->response = $response;
		
		/**
		 * In case of a missing limit, we fall back to the server's post_max_size setting.
		 * This will allow us to determine whether the original request exceeded
		 * maximum size.
		 *
		 * Generally in this situation the request is worthless, since the server is
		 * receiving a truncated request at best. In the case of a custom limit we
		 * can protect certain endpoints from receiving excessively large requests.
		 *
		 */
		try {
			$this->limit = $limit ?? self::parse(ini_get('post_max_size')?: '8M');
		}
		catch (\Exception $e) {
			$this->limit = 8 * 1024 * 1024; // Fallback to 8M if parsing fails
		}
	}
	
	/**
	 * 
	 * @throws \Exception If the configured post_max_size cannot be parsed into bytes.
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
	{
		
		$headers = $request->getHeader('content-length');
		$content_length = $headers ? reset($headers) : false;
		
		if ($content_length !== false && (int)$content_length > $this->limit) {
			return $this->response;
		}
		
		return $handler->handle(($request));
	}
	
	/**
	 * Parses the size provided as a string and returns the size in bytes or
	 * throws on failure.
	 *
	 * This is a copy of the logic used in Filesize::parse to convert human-readable
	 * file size strings into byte values. But we would have to create a
	 * separate repository for it when splitting.
	 *
	 * @param string $str
	 *
	 * @return int
	 * @throws \Exception
	 */
	protected static function parse($str)
	{
		if (!preg_match('/^(\d+)\s*([TGMK])b?$/i', $str, $matches)) {
			throw new \Exception("Unable to parse file size ($str)");
		}
		
		$unit = $matches[2];
		$value = intval($matches[1], 10);
		
		switch (strtoupper($unit)) {
			case 'T':
				$value *= 1024;
				//no break
			case 'G':
				$value *= 1024;
				//no break
			case 'M':
				$value *= 1024;
				//no break
			case 'K':
				$value *= 1024;
		}
		
		return $value;
	}
}
