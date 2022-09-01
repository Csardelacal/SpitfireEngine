<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use spitfire\core\Request;
use spitfire\io\Get;

/*
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */
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
	public function withRequestTarget($requestTarget) : ServerRequestInterface
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
		trigger_error(
			sprintf('Attempted to use Request::withRequestTarget with %s. This has no effect.', $requestTarget),
			E_USER_NOTICE
		);
		
		return $this;
	}
	
	/**
	 *
	 * @param mixed[] $query
	 * @return ServerRequestInterface
	 */
	public function withQueryParams(array $query) : ServerRequestInterface
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
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
	public function getQueryParams() : array
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
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
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
		
		# Sanity check: The resulting object must not be of a different class than the one we started with.
		assert($copy instanceof $this);
		assert($copy instanceof ServerRequestInterface);
		
		$copy->uri = $uri;
		
		return $copy;
	}
	
	/**
	 * This trait depends on the hasHeader function being implemented according to the
	 * spec of the PSR
	 *
	 * @see ServerRequestInterface
	 * @return bool
	 */
	abstract public function hasHeader($name): bool;
	
	/**
	 * This trait depends on the withHeader function being implemented according to the
	 * spec of the PSR
	 *
	 * @see ServerRequestInterface
	 * @return ServerRequestInterface
	 */
	abstract public function withHeader($name, $value): ServerRequestInterface;
}
