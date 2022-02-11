<?php namespace spitfire\storage\database\query;

use spitfire\storage\database\Aggregate;
use spitfire\storage\database\Field;
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

class SelectExpression
{
	
	
	/**
	 * The alias to be providing when the application is generating a SQL string.
	 * This is also the name by which the components depending on the return must
	 * address this field.
	 *
	 * @var string|null
	 */
	private $alias;
	
	/**
	 *
	 * @var FieldReference
	 */
	private $input;
	
	
	/**
	 *
	 * @var Aggregate|null
	 */
	private $aggregate;
	
	/**
	 *
	 * @param FieldReference $input
	 * @param string|null $alias
	 * @param Aggregate|null $aggregate
	 */
	public function __construct(FieldReference $input, string $alias = null, Aggregate $aggregate = null)
	{
		assert($aggregate === null || $alias !== null);
		$this->input = $input;
		$this->alias = $alias;
		$this->aggregate = $aggregate;
	}
	
	/**
	 * The alias to be addressing this alias as.
	 *
	 * @return string
	 */
	public function getAlias() : string
	{
		return $this->alias?: $this->input->getName();
	}
	
	/**
	 * The alias to be addressing this alias as.
	 *
	 * @return bool
	 */
	public function hasAlias() : bool
	{
		return $this->alias !== null;
	}
	
	/**
	 *
	 * @return Aggregate|null
	 */
	public function getAggregate():? Aggregate
	{
		return $this->aggregate;
	}
	
	/**
	 * The alias to be addressing this alias as.
	 *
	 * @param string|null $alias
	 */
	public function setAlias(string $alias = null) : SelectExpression
	{
		$this->alias = $alias;
		return $this;
	}
	
	/**
	 *
	 * @return FieldReference
	 */
	public function getInput(): FieldReference
	{
		return $this->input;
	}
}
