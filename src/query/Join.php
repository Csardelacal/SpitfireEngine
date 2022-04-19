<?php namespace spitfire\storage\database\query;

use Closure;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\FieldReference;
use spitfire\storage\database\identifiers\IdentifierInterface;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
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
 * Joins are used in databases to create cross products of data that can
 * be filtered. Allowing the application to retrieve more specific data from
 * the server without the need to perform multiple trips to the DBMS.
 */
abstract class Join
{
	
	/**
	 * Whether the join is left, right, inner, cross or full outer. Currently spitfire
	 * only supports left inner joins.
	 *
	 * @var string
	 */
	private $direction = 'left';
	
	/**
	 *
	 * @var RestrictionGroup
	 */
	private $on;
	
	/**
	 * Instance a new join. This takes a TableReference (which may connect a physical table or a
	 * temorary table / query).
	 */
	public function __construct()
	{
		$this->on = new RestrictionGroup();
	}
	
	/**
	 * Add a restriction to the table so the join can provide a filter.
	 *
	 * @param mixed $args
	 */
	public function on(...$args) : Join
	{
		$field = $args[0];
		
		switch (count($args)) {
			case 2:
				$operator = '=';
				$operand = $args[1];
				break;
			case 3:
				$operator = $args[1];
				$operand = $args[2];
				break;
			default:
				throw new ApplicationException('Invalid argument count', 2109271126);
		}
		
		assert($field instanceof IdentifierInterface);
		
		$this->on->push(new Restriction($field, $operator, $operand));
		return $this;
	}
	
	/**
	 * Creates a group within the join's restrictions. The closure performs operations on the group,
	 * allowing the application to change the group's type or add restrictions to it.
	 * 
	 * @param Closure(RestrictionGroup) $inner
	 * @return RestrictionGroup
	 */
	public function group(Closure $inner) : Join
	{
		$inner($this->on->group(RestrictionGroup::TYPE_OR));
		return $this;
	}
	
	/**
	 * Returns the direction of the join operation (left, right, inner or outer). Please refer to
	 * the manual of the database management system that you use to understand how these work.
	 *
	 * Spitfire will default to the more common left join, but you could use other join types for
	 * your application.
	 *
	 * @return string
	 */
	public function getDirection() : string
	{
		return $this->direction;
	}
	
	/**
	 * Returns the list of restrictions used to join the two TableReferences, please note that the query
	 * table itself may be a query with restrictions.
	 *
	 * Spitfire defaults to avoiding subqueries with restrictions, since it's very common for these
	 * operations to be executed against a temporary table that needs disk access. So any use of these
	 * will slow down the system in environments where many queries are executed.
	 *
	 * @return RestrictionGroup
	 */
	public function getRestrictions() : RestrictionGroup
	{
		return $this->on;
	}
}
