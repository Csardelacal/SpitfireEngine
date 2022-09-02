<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\ServerRequestInterface;

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
trait HasCookies
{
	
	/**
	 * Allows your app to maintain a copy of the COOKIE variable. This is especially
	 * useful when writing tests considering different requests as you will easily
	 * be able to swap the values.
	 *
	 * @var mixed
	 */
	private $cookie;
	
	
	/**
	 *
	 * @return string[]
	 */
	public function getCookieParams()
	{
		return $this->cookie;
	}
	
	/**
	 *
	 * @param string[] $cookies
	 * @return ServerRequestInterface
	 */
	public function withCookieParams(array $cookies) : ServerRequestInterface
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
		$copy = clone $this;
		$copy->cookie = $cookies;
		return $copy;
	}
}
