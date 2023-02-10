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
 * @template T
 */
class Optional
{
	
	/**
	 * @var T|null
	 */
	private $payload;
	
	/**
	 *
	 * @param T $payload
	 */
	public function __construct($payload = null)
	{
		$this->payload = $payload;
	}
	
	/**
	 * @phpstan-assert-if-true null $this->payload
	 * @return bool
	 */
	public function empty() : bool
	{
		return $this->payload === null;
	}
	
	/**
	 * @phpstan-assert !null $this->payload
	 * @throws OptionalIsEmptyException
	 * @return void
	 */
	public function assert(string $onfailure = '') : void
	{
		if ($this->empty()) {
			throw new OptionalIsEmptyException($onfailure);
		}
	}
	
	/**
	 *
	 * @template P
	 * @param P $alt
	 * @return T|P
	 */
	public function or($alt)
	{
		return $this->empty()? $alt : $this->payload;
	}
	
	/**
	 *
	 * @template P
	 * @param callable():P $alt
	 * @return T|P
	 */
	public function orElse(callable $alt)
	{
		return $this->empty()? $alt() : $this->payload;
	}
	
	/**
	 *
	 * @throws OptionalIsEmptyException
	 * @return T
	 */
	public function get()
	{
		$this->assert();
		return $this->payload;
	}
}
