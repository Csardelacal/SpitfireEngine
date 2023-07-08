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
use spitfire\collection\Collection;
use spitfire\model\Field;
use spitfire\model\reflection\ReflectionField;
use spitfire\model\ReflectionModel;
use spitfire\model\relations\HasMany as RelationsHasMany;
use spitfire\model\relations\RelationshipInterface;

/**
 * @template REMOTE of Model
 * @extends Relationship<REMOTE>
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class HasMany extends Relationship
{
	
	/**
	 * @var class-string<REMOTE>
	 */
	private string $target;
	private string $field;
	
	/**
	 * 
	 * @param class-string<REMOTE> $target
	 * @param string $field
	 */
	public function __construct(string $target, string $field)
	{
		$this->target = $target;
		$this->field = $field;
	}
	
	/**
	 * @template LOCAL of Model
	 * @param ReflectionModel<LOCAL> $context
	 * @param string $name
	 * @return RelationsHasMany<LOCAL,REMOTE>
	 */
	public function newInstance(ReflectionModel $context, string $name) : RelationshipInterface
	{
		/**
		 * @todo The id is hardcoded here
		 * @var RelationsHasMany<LOCAL,REMOTE>
		 */
		return new RelationsHasMany(
			new Field($context, '_id'),
			new Field(new ReflectionModel($this->target), $this->field)
		);
	}
	
	/**
	 * @return Collection<ReflectionField>
	 */
	public function getFields(string $name) : Collection
	{
		/**
		 * @var Collection<ReflectionField>
		 */
		return new Collection();
	}
}
