<?php namespace spitfire\support;

/*
 *
 * Copyright (C) 2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-2023  USA
 *
 */

use spitfire\exceptions\support\OptionalIsEmptyException;

/**
 * The optional class allows us to determine that a value being passed to another
 * function is optional. This allows us to guarantee that it's payload is always
 * not optional (assuming that it is available)
 *
 */
class OptionalExample
{
	/**
	 * @return self|false
	 */
	public function t()
	{
		$optional = new Optional(new OptionalExample);
		
		return $optional->or(false);
	}
	/**
	 * @return ErrorOr<self>
	 */
	public function e()
	{
		
		if (time() % 2 > 0) {
			return ErrorOr::fail('Ahhhh', 1);
		}
		
		return ErrorOr::succeed(new OptionalExample);
	}
}
