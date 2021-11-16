<?php namespace spitfire\storage\database;


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
 * The output class allows the application to define a series of return
 * outputs for the query. Most DBMS allow to write a variation of this
 * statement:
 * 
 * SELECT SUM(field) as a, field2 as b FROM ...
 */
class Output
{
	
	/**
	 * Indicates that a query is accumulating the results and counting them
	 */
	const AGGREGATE_COUNT = 'count';
	
	/**
	 * The alias to be providing when the application is generating a SQL string.
	 * This is also the name by which the components depending on the return must
	 * address this field.
	 * 
	 * @var string|null
	 */
	private $alias;
	
	/**
	 * The field underlying to the output.
	 * 
	 * @todo Replace with the underlying class
	 * @var QueryField|Output
	 */
	private $field;
	
	/**
	 * The operation (if any) to be performed on the resultset before returning it.
	 * 
	 * @var string|null
	 */
	private $operation;
	
	/**
	 * 
	 * @param QueryField|Output $field
	 * @param string|null $operation
	 * @todo Replace with the underlying class
	 */
	public function __construct($field, string $operation = null)
	{
		$this->alias = null;
		$this->field = $field;
		$this->operation = $operation;
	}
	
	/**
	 * 
	 * @return QueryField
	 * @todo Replace with the underlying class
	 */
	public function getField(): QueryField 
	{
		return $this->field;
	}
	
	/**
	 * The operation to be performing on the field before returning it to the
	 * output.
	 * 
	 * @see Output::AGGREGATE_*
	 * @return string|null
	 */
	public function getOperation() :? string
	{
		return $this->operation;
	}
	
	
	/**
	 * The alias to be addressing this output as.
	 * 
	 * @return string|null
	 */
	public function getAlias() :? string 
	{
		return $this->alias;
	}
	
	/**
	 * Sets the field to retrieve data from to be serving it to the output.
	 * 
	 * @param QueryField $field
	 * @todo Replace with the underlying class
	 */
	public function setField(QueryField $field) 
	{
		$this->field = $field;
		return $this;
	}
	
	/**
	 * The operation to be performing on the field before returning it to the
	 * output.
	 * 
	 * @see Output::AGGREGATE_*
	 * @param string|null $operation
	 */
	public function setOperation(string $operation = null)
	{
		$this->operation = $operation;
		return $this;
	}
	
	/**
	 * The alias to be addressing this output as.
	 * 
	 * @param string|null $alias
	 */
	public function setAlias(string $alias = null) 
	{
		$this->alias = $alias;
		return $this;
	}
	
}
