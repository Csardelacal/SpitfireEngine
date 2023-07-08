<?php namespace spitfire\model;
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


use spitfire\model\Model;

/**
 * Represents a table's field in a database. Contains information about the
 * table the field belongs to, the name of the field and if it is (or not) a
 * primary key or auto-increment field.
 *
 * @template T of Model
 * @author César de la Cal <cesar@magic3w.com>
 */
class Field
{
	
	/**
	 * 
	 * @var ReflectionModel<T>
	 */
	private ReflectionModel $model;
	
	/**
	 * The name of the field inside the Model.
	 * 
	 * @var string
	 */
	private string $name;
	
	/**
	 * 
	 * @param ReflectionModel<T> $model
	 * @param string $field
	 */
	public function __construct(ReflectionModel $model, string $field)
	{
		$this->model = $model;
		$this->name = $field;
	}
	
	/**
	 * Returns the Reflection of the Model the Field references.
	 *
	 * @return ReflectionModel<T>
	 */
	public function getModel() : ReflectionModel
	{
		return $this->model;
	}
	
	public function getName() : string
	{
		return $this->name;
	}
	
	/**
	 *
	 * @deprecated
	 * @see Field::getName
	 */
	public function getField() : string
	{
		return $this->name;
	}
}
