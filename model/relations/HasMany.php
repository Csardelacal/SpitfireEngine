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


use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\model\ActiveRecord;
use spitfire\model\Model;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\QueryBuilder;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\query\RestrictionGroup;
use spitfire\storage\database\Record;

/**
 * The belongsTo relationship allows an application to indicate that this
 * model is part of a 1:n relationship with another model.
 *
 * In this case, the model using this relationship is the n part or the
 * child. This makes it a single relationship, since models using this
 * relationship will have a single parent.
 * 
 * @template LOCAL of Model
 * @template REMOTE of Model
 * @extends Relationship<LOCAL,REMOTE>
 */
class HasMany extends Relationship
{
	
	public function resolve(ActiveRecord $record): RelationshipContent
	{
		
		/**
		 * Start by querying the referenced model. This is the model for which we
		 * wish to return data.
		 */
		$query = new QueryBuilder($record->getConnection(), $this->getReferenced()->getModel());
		
		/**
		 * Find the records that belong to this one.
		 */
		$query->where(
			$this->getReferenced()->getName(),
			$record->get($this->getField()->getName())
		);
		
		/**
		 * We execute the query with all(). As opposed to belongsToOne, this one can
		 * contain any amount of records.
		 */
		$result = $query->all();
		
		/**
		 * If the result is not empty, we perform a sanity check to ensure that the model
		 * we received is of the type we expected.
		 */
		assert($result->containsOnly(get_class($this->getReferenced()->getModel())));
		
		return new RelationshipContent(false, $result);
	}
	
	public function resolveAll(Collection $records): Collection
	{
		
		if ($records->isEmpty()) {
			return new Collection(new RelationshipContent(false, new TypedCollection(Model::class)));
		}
		
		/**
		 * Start by querying the referenced model. This is the model for which we
		 * wish to return data.
		 */
		$query = (new QueryBuilder(
			$records->first()->getConnection(),
			$this->getReferenced()->getModel()
		))->withDefaultMapping();
		
		/**
		 * We create a restriction group that performs an OR on all our available records.
		 * For example, if we pass a series of records with the ID 1, 2 and 3; our query
		 * will generate a restriction like WHERE (ref_id =1 OR ref_id=2 OR ref_id=3) which
		 * allows to retrieve all the referenced items in a single go.
		 */
		$query->group(RestrictionGroup::TYPE_OR, function (RestrictionGroupBuilder $group) use ($records) {
			foreach ($records as $record) {
				assert($record instanceof ActiveRecord);
				
				$group->where(
					$this->getReferenced()->getName(),
					$record->get($this->getField()->getName())
				);
			}
		});
		
		/**
		 * Retrieve all the records we can from the database. We then can proceed to sort them into our
		 * resultsets.
		 */
		$result = $query->all();
		
		$_return = $result->groupBy(function (Model $item) {
			/**
			 * Health check: See if the resulting model is actually the type that we were expecting
			 */
			assert(get_class($item) === $this->getReferenced()->getModel()->getClassname());
			
			/**
			 * Group the items by their referenced ID. Please note that on the remote table this
			 * should be a primary key, which means there must be no duplicates. We will ensure this
			 * is the case in the next step.
			 */
			return $item->get($this->getReferenced()->getName());
		})
		->each(function (Collection $item) : RelationshipContent {
			return new RelationshipContent(false, $item);
		});
		
		return $_return;
	}
	
	/**
	 * @return DirectRelationshipInjector<LOCAL,REMOTE>
	 */
	public function injector(): RelationshipInjectorInterface
	{
		return new DirectRelationshipInjector($this->getField(), $this->getReferenced());
	}
	
	public function startQueryBuilder(ActiveRecord $parent): QueryBuilder
	{
		$query = (new QueryBuilder(
			$parent->getConnection(),
			$this->getReferenced()->getModel()
		))->withDefaultMapping();
		
		$query->where(
			$this->getReferenced()->getName(),
			$parent->get($this->getField()->getName())
		);
		
		return $query;
	}
}
