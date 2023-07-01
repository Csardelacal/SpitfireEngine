<?php namespace spitfire\storage\database;

/*
 *
 * Copyright (C) 2021-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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

/**
 * The output class allows the application to define a series of return
 * outputs for the query. Most DBMS allow to write a variation of this
 * statement:
 *
 * SELECT SUM(field) as a, field2 as b FROM ...
 */
class Aggregate
{
	
	/**
	 * Indicates that a query is accumulating the results and counting them
	 */
	const AGGREGATE_COUNT = 'count';
	
	/**
	 * Indicates that a query is accumulating the results and adding them all together
	 */
	const AGGREGATE_SUM = 'sum';
	
	/**
	 * The operation (if any) to be performed on the resultset before returning it.
	 *
	 * @var string
	 */
	private $operation;
	
	/**
	 *
	 * @param string $operation
	 */
	public function __construct(string $operation)
	{
		$this->operation = $operation;
	}
	
	/**
	 * The operation to be performing on the field before returning it to the
	 * output.
	 *
	 * @see Aggregate::AGGREGATE_*
	 * @return string
	 */
	public function getOperation() : string
	{
		return $this->operation;
	}
	
	/**
	 * The operation to be performing on the field before returning it to the
	 * output.
	 *
	 * @see Aggregate::AGGREGATE_*
	 * @param string $operation
	 */
	public function setOperation(string $operation) : Aggregate
	{
		$this->operation = $operation;
		return $this;
	}
}
