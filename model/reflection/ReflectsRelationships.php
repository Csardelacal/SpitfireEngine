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
use spitfire\collection\TypedCollection;
use spitfire\model\attribute\BelongsToOne;
use spitfire\model\attribute\HasMany;
use spitfire\model\attribute\Relationship;
use spitfire\model\Model;
use spitfire\model\ReflectionModel;
use spitfire\model\relations\RelationshipInterface;

trait ReflectsRelationships
{
	
	/**
	 * @var Collection<ReflectionRelationship>
	 */
	private Collection $relationships;
	
	/**
	 * @return Collection<ReflectionRelationship>
	 */
	public function getRelationShips() : Collection
	{
		return $this->relationships;
	}
	
	/**
	 * @param ReflectionClass<Model> $source
	 */
	private function makeRelationships(ReflectionClass $source) : void
	{
		
		$this->relationships = new TypedCollection(ReflectionRelationship::class);
		$props = $source->getProperties();
		
		/**
		 * @todo Add attributes for something like belongsToMany
		 */
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
				
				$name = $prop->getName();
				
				assert($this instanceof ReflectionModel);
				$this->relationships[$name] = new ReflectionRelationship($this, $name, $relationship);
			}
		}
	}
}
