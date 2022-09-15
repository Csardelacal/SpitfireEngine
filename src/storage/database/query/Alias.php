<?php namespace spitfire\storage\database\query;

use spitfire\storage\database\identifiers\TableIdentifierInterface;

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
	 * @var QueryOrTableIdentifier
	 */
	private $input;
	
	/**
	 *
	 * @var TableIdentifierInterface
	 */
	private $output;
	
	public function __construct(QueryOrTableIdentifier $input, TableIdentifierInterface $output)
	{
		$this->input = $input;
		$this->output = $output;
	}
	
	public function input() : QueryOrTableIdentifier
	{
		return $this->input;
	}
	
	public function output() : TableIdentifierInterface
	{
		return $this->output;
	}
}
