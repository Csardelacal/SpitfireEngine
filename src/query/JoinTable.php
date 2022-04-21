<?php namespace spitfire\storage\database\query;

use spitfire\storage\database\identifiers\IdentifierInterface;

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
 * Joins are used in databases to create cross products of data that can
 * be filtered. Allowing the application to retrieve more specific data from
 * the server without the need to perform multiple trips to the DBMS.
 */
class JoinTable extends Join
{
	
	/**
	 *
	 * @var Alias
	 */
	private $alias;
	
	/**
	 * Instance a new join. This takes a Alias (which may connect a physical table or a
	 * temorary table / query).
	 *
	 * @param Alias $alias
	 */
	public function __construct(Alias $alias)
	{
		$this->alias = $alias;
		parent::__construct($alias->output());
	}
	
	/**
	 * The query table that is used as a source. Please note that a query table can contain a query returning
	 * a temp table that is used to retrieve a subset of the original relation.
	 *
	 * @return Alias
	 */
	public function getAlias() : Alias
	{
		return $this->alias;
	}
	
	/**
	 * Gets a reference to a field within the joined table. The table will be already prefixed and aliased.
	 *
	 * @param string $name
	 * @return IdentifierInterface
	 */
	public function getOutput(string $name) : IdentifierInterface
	{
		return $this->alias->output()->getOutput($name);
	}
}
