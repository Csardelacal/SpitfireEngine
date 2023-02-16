<?php namespace spitfire\support;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-13 01  USA
 *
 */

use spitfire\exceptions\support\UnhandledErrorException;

/**
 * This class allows us to retrieve an error from a function (if it had any)
 * or lets us retrieve the value of it.
 *
 * @template T
 */
class ErrorOr
{
	
	
	/**
	 * @var ?Error
	 */
	private ?Error $error;
	
	/**
	 * @var ?T
	 */
	private $value;
	
	/**
	 * @param ?Error $error
	 * @param ?T $value
	 */
	private function __construct(?Error $error, $value)
	{
		$this->error = $error;
		$this->value = $value;
	}
	
	/**
	 * @phpstan-assert-if-true null $this->error
	 * @phpstan-assert-if-true !null $this->value
	 * @return bool
	 */
	public function success() : bool
	{
		return $this->error === null;
	}
	
	/**
	 * @phpstan-assert null $this->error
	 * @phpstan-assert !null $this->value
	 * @throws UnhandledErrorException
	 * @return void
	 */
	public function assert(string $onfailure = '') : void
	{
		if (!$this->success()) {
			throw new UnhandledErrorException($onfailure);
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
		return $this->success()? $this->value : $alt;
	}
	
	/**
	 *
	 * @template P
	 * @param callable():P $alt
	 * @return T|P
	 */
	public function orElse(callable $alt)
	{
		return $this->success()? $this->value : $alt();
	}
	
	/**
	 *
	 * @throws UnhandledErrorException
	 * @return T
	 */
	public function releaseValue()
	{
		$this->assert();
		return $this->value;
	}
	
	/**
	 *
	 * @return ?Error
	 */
	public function releaseError() :? Error
	{
		return $this->error;
	}
	
	/**
	 *
	 * @return ErrorOr<mixed>
	 */
	public static function fail(string $msg, int $code)
	{
		/**
		 * @var ErrorOr<mixed>
		 */
		return new ErrorOr(new Error($msg, $code), null);
	}
	
	/**
	 *
	 * @template L
	 * @param L $payload
	 * @return ErrorOr<L>
	 */
	public static function succeed($payload)
	{
		return new ErrorOr(null, $payload);
	}
}
