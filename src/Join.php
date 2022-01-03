<?php namespace spitfire\storage\database;

use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\query\TableObjectInterface;

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
class Join
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
	 * @var TableObjectInterface
	 */
	private $table;
	
	/**
	 * 
	 * @var Collection<Restriction>
	 */
	private $on;
	
	/**
	 * Instance a new join. This takes a TableObjectInterface (which may connect a physical table or a 
	 * temorary table / query).
	 * 
	 * @param TableObjectInterface $table
	 */
	public function __construct(TableObjectInterface $table)
	{
		$this->table = $table;
	}
	
	/**
	 * Add a restriction to the table so the join can provide a filter.
	 * 
	 * @param mixed $args
	 */
	public function on(... $args) : Join
	{
		$field = $args[0];
		
		switch(count($args)) {
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
		
		assert($field instanceof QueryField);
		
		$this->on->push(new Restriction($field, $operator, $operand));
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
	 * Returns the list of restrictions used to join the two TableObjectInterfaces, please note that the query
	 * table itself may be a query with restrictions.
	 * 
	 * Spitfire defaults to avoiding subqueries with restrictions, since it's very common for these 
	 * operations to be executed against a temporary table that needs disk access. So any use of these
	 * will slow down the system in environments where many queries are executed.
	 * 
	 * @return Collection<Restriction>
	 */
	public function getRestrictions() : Collection
	{
		return $this->on;
	}
	
	/**
	 * The query table that is used as a source. Please note that a query table can contain a query returning
	 * a temp table that is used to retrieve a subset of the original relation.
	 * 
	 * @return TableObjectInterface
	 */
	public function getTable() : TableObjectInterface
	{
		return $this->table;
	}
}
