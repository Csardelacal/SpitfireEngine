<?php namespace spitfire\storage\database\query;

use spitfire\storage\database\FieldReference;
use spitfire\storage\database\TableReference;

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
 * 
 */
class Alias
{
	
	/**
	 * 
	 * @var TableReference
	 */
	private $input;
	
	/**
	 * 
	 * @var TableReference
	 */
	private $output;
	
	public function __construct(TableReference $input, TableReference $output)
	{
		$this->input = $input;
		$this->output = $output;
	}
	
	public function input() : TableReference
	{
		return $this->input;
	}
	
	public function output() : TableReference
	{
		return $this->output;
	}
}
