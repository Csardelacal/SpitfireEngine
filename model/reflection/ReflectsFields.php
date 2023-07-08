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


use ReflectionClass;
use spitfire\collection\Collection;
use spitfire\model\attribute\BelongsToOne;
use spitfire\model\attribute\Type;
use spitfire\model\attribute\CharacterString;
use spitfire\model\attribute\EnumType;
use spitfire\model\attribute\HasMany;
use spitfire\model\attribute\Text as TextAttribute;
use spitfire\model\attribute\Integer as IntAttribute;
use spitfire\model\attribute\LongInteger as LongAttribute;
use spitfire\model\attribute\Relationship;
use spitfire\model\Model;

trait ReflectsFields
{
	
	/**
	 *
	 * @var Collection<ReflectionField>
	 */
	private Collection $fields;
	
	/**
	 * 
	 * @param ReflectionClass<Model> $source
	 */
	public function makeFields(ReflectionClass $source) : void
	{
		/**
		 * @var Collection<ReflectionField>
		 */
		$fields = new Collection;
		$this->fields = $fields;
		$this->makeFieldsFromProperties($source);
		$this->makeFieldsFromRelationsiphs($source);
	}
	
	public function hasField(string $name) : bool
	{
		return $this->fields->has($name);
	}
	
	/**
	 * Creates fields from the properties of the model. A field is any property that has
	 * a database type annotaation.
	 * 
	 * @param ReflectionClass<Model> $source
	 * @return void
	 */
	private function makeFieldsFromProperties(ReflectionClass $source) : void
	{
		$props = $source->getProperties();
		
		
		$available = [
			IntAttribute::class,
			LongAttribute::class,
			CharacterString::class,
			TextAttribute::class,
			EnumType::class
		];
		
		foreach ($props as $prop) {
			/**
			 * This prevents an application from registering two types to a single
			 * column, which would lead to disaster.
			 */
			$found = false;
			
			foreach ($available as /** @var class-string */ $type) {
				/**
				 * Check if the column is of type
				 */
				$columnAttribute = $prop->getAttributes($type);
				
				/**
				 * If the property is not part of a field, we just continue.
				 */
				if (empty($columnAttribute)) {
					continue;
				}
				
				assert(count($columnAttribute) === 1);
				assert($found === false);
				
				$found = true;
				
				$column = $columnAttribute[0]->newInstance();
				assert($column instanceof Type);
				
				if ($prop->getType() !== null) {
					$nullable = $prop->getType()->allowsNull();
				}
				else {
					$nullable = true;
				}
				
				$name = $prop->getName();
				$this->fields[$name] = new ReflectionField($name, $nullable, $column);
			}
		}
	}
	
	/**
	 * Creates fields from the relationships of the model. A relationship is any property that has
	 * a relationship annotation.
	 * 
	 * @param ReflectionClass<Model> $source
	 * @return void
	 */
	private function makeFieldsFromRelationsiphs(ReflectionClass $source)
	{
		
		$props = $source->getProperties();
		
		$available = [
			BelongsToOne::class,
			HasMany::class
		];
		
		foreach ($props as $prop) {
			/**
			 * This prevents an application from registering two types to a single
			 * column, which would lead to disaster.
			 */
			$found = false;
			
			foreach ($available as /** @var class-string */ $type) {
				/**
				 * Check if the column is of type
				 */
				$relationshipAttribute = $prop->getAttributes($type);
				
				/**
				 * If the property is not part of a field, we just continue.
				 */
				if (empty($relationshipAttribute)) {
					continue;
				}
				
				assert(count($relationshipAttribute) === 1);
				assert($found === false);
				
				$found = true;
				
				$relationship = $relationshipAttribute[0]->newInstance();
				assert($relationship instanceof Relationship);
				
				$this->fields->add($relationship->getFields($prop->getName()));
			}
		}
	}
	
	/**
	 *
	 * @return Collection<ReflectionField>
	 */
	public function getFields() : Collection
	{
		return $this->fields;
	}
	
	public function getField(string $name) : ReflectionField
	{
		assert($this->hasField($name));
		return $this->fields[$name];
	}
}
