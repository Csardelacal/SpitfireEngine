<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\ServerRequestInterface;
use spitfire\core\Headers;
use spitfire\core\Request;
use spitfire\exceptions\ApplicationException;

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
trait HasHeaders
{
	
	/**
	 * This object allows your app to conveniently access the HTTP headers. These
	 * will contain information like DNT or User agent that can be relevant to your
	 * application and alter the experience the user receives.
	 *
	 * @var Headers
	 */
	private Headers $headers;
	
	public function getHeaders()
	{
		return $this->headers->all();
	}
	
	
	/**
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasHeader($name): bool
	{
		return !empty($this->headers->get($name));
	}
	
	/**
	 *
	 * @param string $name
	 * @return string[]
	 */
	public function getHeader($name)
	{
		return $this->headers->get($name);
	}
	
	/**
	 * The header line represents the data that is being sent to the browser, some headers
	 * allow sending multiple values, separated by commas, which the client cannot receive
	 * unless we convert our arrays in comma separated strings.
	 *
	 * @param string $name
	 * @return string
	 */
	public function getHeaderLine($name): string
	{
		$header = $this->headers->get($name);
		
		if (empty($header)) {
			return implode(',', $header);
		}
		else {
			return '';
		}
	}
	
	/**
	 * Returns a copy of the response, with the header that the user has added.
	 *
	 * @param string $name
	 * @param string|string[] $value
	 * @return self
	 */
	public function withHeader($name, $value): self
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
		$headers = clone $this->headers;
		$headers->set($name, (array)$value);
		
		$copy = clone $this;
		$copy->headers = $headers;
		return $copy;
	}
	
	/**
	 * Returns a copy of the response, but with additional information on a header,
	 * which allows the user to push data onto the header.
	 *
	 * @param string $name
	 * @param string|string[] $value
	 * @return self
	 */
	public function withAddedHeader($name, $value): self
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
		$headers = clone $this->headers;
		$headers->addTo($name, (array)$value);
		
		$copy = clone $this;
		$copy->headers = $headers;
		return $copy;
	}
	
	/**
	 * Returns a copy of the response, without the given header.
	 *
	 * @param string $name
	 * @return self
	 */
	public function withoutHeader($name): self
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
		$headers = clone $this->headers;
		$headers->unset($name);
		
		$copy = clone $this;
		$copy->headers = $headers;
		return $copy;
	}
	
	/**
	 * If the request contains range information (the client only wishes to retrieve a subset of the
	 * resource), this endpoint will return true.
	 *
	 * @return bool
	 */
	public function isRange() : bool
	{
		return !empty($this->headers->get('Range'));
	}
	
	/**
	 * For ranged requests, this function provides a convenience access to the range data.
	 * This method only supports ranges in Bytes
	 *
	 * @return array{int,int|null}
	 */
	public function getRange() : array
	{
		$sent = $this->headers->get('Range')[0];
		
		if (!\spitfire\utils\Strings::startsWith($sent, 'bytes=')) {
			throw new ApplicationException('Malformed range sent', 416);
		}
		
		if (strstr($sent, ',')) {
			throw new ApplicationException('Spitfire does not accept multiple ranges', 416);
		}
		
		$pieces = explode('-', substr($_SERVER['HTTP_RANGE'], 6));
		
		return [
			(int)array_shift($pieces),
			(int)array_shift($pieces)?: null
		];
	}
}
