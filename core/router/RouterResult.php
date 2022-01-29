<?php namespace spitfire\core\router;

use Psr\Http\Server\RequestHandlerInterface;
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

/**
 * The router result object is returned when a route matches (or not) a request.
 */
class RouterResult
{
	/**
	 * 
	 * @var RequestHandlerInterface|null
	 */
	private $handler;
	
	public function __construct(RequestHandlerInterface $handler = null)
	{
		$this->handler = $handler;
	}
	
	/**
	 * Allows the result object to report whether the router was able to 
	 * find a route that matches the request that it received.
	 * 
	 * @return bool
	 */
	public function success() : bool
	{
		return $this->handler !== null;
	}
	
	/**
	 * Returns a requesthandler that the router suggested for handlign the request.
	 * If the router did not match anything, this method will throw an exception
	 * to report that the requesthandler cannot be used.
	 * 
	 * @throws ApplicationException
	 * @return RequestHandlerInterface
	 */
	public function getHandler(): RequestHandlerInterface
	{
		/*
		 * Most of the time, we'll avoid stepping into this exception by just making sure
		 * that we call success before actually trying to retrieve the handler.
		 */
		if ($this->handler === null) {
			throw new ApplicationException('Router was unable to match any route', 2106151027);
		}
		
		return $this->handler;
	}
}
