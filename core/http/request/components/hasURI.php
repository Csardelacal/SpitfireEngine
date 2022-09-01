<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use spitfire\core\Request;
use spitfire\io\Get;

trait hasURI
{
	/**
	 * Contains data that can be retrieved out of the Path string of the URL. For
	 * example, the controller / action / object of the request or data about
	 * custom route parameters.
	 *
	 * @var UriInterface
	 */
	private $uri;
	
	/**
	 * Contains information what information was delivered via query parameters of
	 * the URL. This will be a special _GET object that will aid generating canonicals.
	 * If you're writing an application/component that cannot guarantee having the
	 * <i>request.replace_globals</i> enabled you should use this rather than $_GET
	 *
	 * @var \spitfire\io\Get
	 */
	private $get;
	
	/**
	 * Returns the URI used to request a resource from the server.
	 *
	 * @return UriInterface
	 */
	public function getUri() : UriInterface
	{
		return $this->uri;
	}
	
	/**
	 * Returns the request target. I am still not 100% certain what the request target is, it seems
	 * to be some kind of fallback that I am not certain how it works (apparently you can request
	 * wildcard data from some servers).
	 *
     * @see https://www.rfc-editor.org/rfc/rfc7230.txt (section 5.3.1)
	 * @return string
	 */
	public function getRequestTarget() : string
	{
		if ($this->uri === null) {
			return '/';
		}
		
		$query = $this->uri->getQuery();
		return $this->uri->getPath() . ($query? '?' . $query: '');
	}
	
	/**
	 * In our service this is not implemented. Calling it has no effect
	 */
	public function withRequestTarget($requestTarget) : Request
	{
		trigger_error(
			sprintf('Attempted to use Request::withRequestTarget with %s. This has no effect.', $requestTarget), 
			E_USER_NOTICE
		);
		
		return $this;
	}
	
	/**
	 *
	 * @param mixed[] $query
	 * @return Request
	 */
	public function withQueryParams(array $query) : Request
	{
		$copy = clone $this;
		$copy->get = new Get($query);
		$copy->uri = $this->uri->withQuery(http_build_query($query));
		
		return $copy;
	}
	
	/**
	 * Returns the array of query parameters (_GET) that the request was sent along
	 * with.
	 *
	 * @return mixed[]
	 */
	public function getQueryParams()
	{
		if ($this->get === null) {
			parse_str($this->uri->getQuery(), $get);
			$this->get = new Get($get);
		}
		
		return $this->get->getRaw();
	}
	
	/**
	 * Override the request URI for the server.
	 *
	 * @return ServerRequestInterface
	 */
	public function withUri(UriInterface $uri, $preserveHost = false) : ServerRequestInterface
	{
		if ($preserveHost) {
			if (!$this->hasHeader('Host') && $uri->getHost()) {
				$copy = $this->withHeader('Host', $uri->getHost());
			}
			else {
				$copy = clone $this;
			}
		}
		else {
			$copy = $this->withHeader('Host', $uri->getHost());
		}
		
		
		$copy->uri = $uri;
		
		return $copy;
	}
}
