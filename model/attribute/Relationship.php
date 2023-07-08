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

use spitfire\collection\Collection;
use spitfire\model\Model;
use spitfire\model\reflection\ReflectionField;
use spitfire\model\ReflectionModel;
use spitfire\model\relations\RelationshipInterface;

/**
 * 
 * @template REMOTE of Model
 */
abstract class Relationship
{
	
	/**
	 * 
	 * @template LOCAL of Model
	 * @param ReflectionModel<LOCAL> $context
	 * @param string $name
	 * @return RelationshipInterface<LOCAL,REMOTE>
	 */
	abstract public function newInstance(ReflectionModel $context, string $name) : RelationshipInterface;
	
	/**
	 * 
	 * @param string $name
	 * @return Collection<ReflectionField>
	 */
	abstract public function getFields(string $name) : Collection;
}
