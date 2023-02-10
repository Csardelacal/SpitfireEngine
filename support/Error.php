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

class Error
{
	
	private string $msg;
	private int $code;
	
	public function __construct(string $msg, int $code)
	{
		$this->msg = $msg;
		$this->code = $code;
	}
	
	/**
	 * Get the value of code
	 *
	 * @return int
	 */
	public function getCode(): int
	{
		return $this->code;
	}
	
	/**
	 * Set the value of code
	 *
	 * @param int $code
	 *
	 * @return self
	 */
	public function setCode(int $code): self
	{
		$this->code = $code;
		
		return $this;
	}
	
	/**
	 * Get the value of msg
	 *
	 * @return string
	 */
	public function getMsg(): string
	{
		return $this->msg;
	}
	
	/**
	 * Set the value of msg
	 *
	 * @param string $msg
	 *
	 * @return self
	 */
	public function setMsg(string $msg): self
	{
		$this->msg = $msg;
		
		return $this;
	}
}
