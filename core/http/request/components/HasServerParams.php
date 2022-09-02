<?php namespace spitfire\core\http\request\components;

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
trait HasServerParams
{
	
	/**
	 * The server parameter array contains all the runtime information the server
	 * has about itself and the current request. Usually this data is directly
	 * originated from $_SERVER
	 *
	 * @var string[]
	 */
	private $serverParams;
	
	/**
	 * The server params function returns the equivalent of the _SERVER
	 * superglobal. We generally set the content of the variable to _SERVER
	 * but you may come across implementations that override it completely
	 * or partially.
	 *
	 * @return string[]
	 */
	public function getServerParams()
	{
		return $this->serverParams;
	}
}
