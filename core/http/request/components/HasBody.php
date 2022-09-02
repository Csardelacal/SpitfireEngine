<?php namespace spitfire\core\http\request\components;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

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
trait HasBody
{
	
	/**
	 *
	 * @var StreamInterface
	 */
	private $body;
	
	/**
	 * Returns the content that is to be sent with the body. This is a string your
	 * application has to set beforehand.
	 *
	 * In case you're using Spitfire's Context object to manage the context the
	 * response will get the view it contains and render that before returning it.
	 *
	 * @return StreamInterface
	 */
	public function getBody() : StreamInterface
	{
		return $this->body;
	}
	
	public function withBody(StreamInterface $body) : ServerRequestInterface
	{
		#For this trait to work properly it needs to be applied to a class that implements
		#the request interface.
		assert($this instanceof ServerRequestInterface);
		
		$copy = clone $this;
		$copy->body = $body;
		return $copy;
	}
}
