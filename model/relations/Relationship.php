<?php namespace spitfire\model\relations;
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


use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\QueryBuilder;
use spitfire\model\ReflectionModel;
use spitfire\storage\database\ConnectionInterface;
use spitfire\utils\Mixin;

/**
 *
 * @template LOCAL of Model
 * @template REMOTE of Model
 * @implements RelationshipInterface<LOCAL,REMOTE>
 * @mixin QueryBuilder<REMOTE>
 */
abstract class Relationship implements RelationshipInterface
{
	
	use Mixin;
	
	/**
	 *
	 * @var Field<LOCAL>
	 */
	private Field $field;
	
	/**
	 *
	 * @var Field<REMOTE>
	 */
	private Field $referenced;
	
	/**
	 *
	 * @param Field<LOCAL> $field
	 * @param Field<REMOTE> $referenced
	 *
	 */
	public function __construct(Field $field, Field $referenced)
	{
		$this->field = $field;
		$this->referenced = $referenced;
		
		/**
		 * If this object receives a function call that it cannot handle, forward
		 * it to the querybuilder.
		 * 
		 * @todo THis exits into the global scope which should not be necessary
		 */
		$this->mixin(fn() => new QueryBuilder(
			spitfire()->provider()->get(ConnectionInterface::class),
			$this->getReferenced()->getModel()
		));
	}
	
	/**
	 * 
	 * @return ReflectionModel<REMOTE>
	 */
	public function getModel(): ReflectionModel
	{
		return $this->referenced->getModel();
	}
	
	/**
	 *
	 * @return Field<LOCAL>
	 */
	public function getField() : Field
	{
		return $this->field;
	}
	
	/**
	 *
	 * @return Field<LOCAL>
	 */
	public function localField() : Field
	{
		return $this->field;
	}
	
	/**
	 *
	 * @return Field<REMOTE>
	 */
	public function getReferenced() : Field
	{
		return $this->referenced;
	}
	
	abstract public function injector() : RelationshipInjectorInterface;
}
