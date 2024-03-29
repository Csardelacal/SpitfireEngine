<?php namespace spitfire\model\attribute;
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


use Attribute;
use spitfire\model\Model;

/**
 * This attribute allows a programmer to determine whether the column references another
 * column in another table. This is required for relationships to work properly and for
 * the DBMS to understand the data it contains and the relations between it.
 *
 * In most DBMS this attribute is equivalent to a foreign key. Since Spitfire does not support
 * foreign keys (as in foreign keys spanning multiple columns), we refer to this attribute
 * by a different name.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class References
{
	
	/**
	 *
	 * @var class-string<Model>
	 */
	private string $model;
	
	/**
	 *
	 * @param class-string<Model> $model
	 */
	public function __construct(string $model)
	{
		$this->model = $model;
	}
	
	/**
	 * Get the value of table
	 *
	 * @return  class-string<Model>
	 */
	public function getModel() : string
	{
		return $this->model;
	}
}
