<?php namespace spitfire\model\reflection;

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

use spitfire\model\attribute\Relationship;
use spitfire\model\ReflectionModel;
use spitfire\model\relations\RelationshipInterface;

class ReflectionRelationship
{
	private ReflectionModel $model;
	private string $name;
	private Relationship $relationship;
	
	public function __construct(ReflectionModel $model, string $name, Relationship $relationship)
	{
		$this->model = $model;
		$this->name = $name;
		$this->relationship = $relationship;
	}
	
	/**
	 * Get the value of type
	 *
	 * @return Relationship
	 */
	public function getRelationship(): Relationship
	{
		return $this->relationship;
	}
	
	/**
	 * Get the value of name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}
	
	public function newInstance() : RelationshipInterface
	{
		return $this->relationship->newInstance($this->model, $this->name);
	}
	
}
