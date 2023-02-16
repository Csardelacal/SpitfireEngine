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
use spitfire\model\attribute\Type;
use spitfire\model\attribute\CharacterString;
use spitfire\model\attribute\EnumType;
use spitfire\model\attribute\Text as TextAttribute;
use spitfire\model\attribute\Integer as IntAttribute;
use spitfire\model\attribute\LongInteger as LongAttribute;

trait ReflectsFields
{
	
	/**
	 *
	 * @var Collection<ReflectionField>
	 */
	private Collection $fields;
	
	public function makeFields(ReflectionClass $source)
	{
		/**
		 * @var Collection<ReflectionField>
		 */
		$fields = new Collection;
		$this->fields = $fields;
		$this->makeFieldsFromProperties($source);
	}
	
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
				
				$name = $prop->getName();
				$this->fields[$name] = new ReflectionField($name, $prop->getType()->allowsNull(), $column);
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
}
