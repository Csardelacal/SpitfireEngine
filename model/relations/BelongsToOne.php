<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\model\ActiveRecord;
use spitfire\model\Model;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\QueryBuilder;
use spitfire\storage\database\query\RestrictionGroup;

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
class BelongsToOne extends Relationship
{
	
	
	public function resolve(ActiveRecord $record): RelationshipContent
	{
		
		$value = $record->getUnderlyingRecord()->get($this->getField()->getName());
		
		/**
		 * If the value is null, there is no referenced element available. Since null
		 * implies, by convention that there is nothing on the other side.
		 */
		if ($value === null) {
			return new RelationshipContent(true, new TypedCollection(Model::class));
		}
		
		/**
		 * Start by querying the referenced model. This is the model for which we
		 * wish to return data.
		 */
		$query = $this->getReferenced()->getModel()->query();
		
		/**
		 * Find the record that this one belongs to. Please note that this is a single
		 * record. This relationship does not support returning multiple ones.
		 */
		$query->where(
			$this->getReferenced()->getName(),
			$value
		);
		
		/**
		 * We execute the query with all(), even though there may only be one record, but
		 * the idea behind it is that the Content needs a Collection anyway, and it allows
		 * us to healthcheck the system with assertions.
		 */
		$result = $query->all();
		
		assert($result->count() === 1);
		assert($result->containsOnly(get_class($this->getReferenced()->getModel())));
		
		return new RelationshipContent(true, $result);
	}
	
	public function resolveAll(Collection $records): Collection
	{
		/**
		 * Start by querying the referenced model. This is the model for which we
		 * wish to return data.
		 */
		$query = $this->getReferenced()->getModel()->query();
		
		/**
		 * We create a restriction group that performs an OR on all our available records.
		 * For example, if we pass a series of records with the ID 1, 2 and 3; our query
		 * will generate a restriction like WHERE (ref_id =1 OR ref_id=2 OR ref_id=3) which
		 * allows to retrieve all the referenced items in a single go.
		 */
		$query->group(RestrictionGroup::TYPE_OR, function (RestrictionGroupBuilder $group) use ($records) {
			foreach ($records as $record) {
				assert($record instanceof ActiveRecord);
				assert(get_class($record->getModel()) === get_class($this->getField()->getModel()));
				
				$group->where(
					$this->getReferenced()->getName(),
					$record->getUnderlyingRecord()->get($this->getField()->getName())
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
			assert(get_class($item) === get_class($this->getReferenced()->getModel()));
			
			/**
			 * Group the items by their referenced ID. Please note that on the remote table this
			 * should be a primary key, which means there must be no duplicates. We will ensure this
			 * is the case in the next step.
			 */
			return $item->get($this->getReferenced()->getName());
		})
		->each(function (Collection $item) : RelationshipContent {
			/**
			 * Since this is a belongsToOne relationship, a simple healthcheck we can perform is to make
			 * sure that the collection we're planning to return contains only one item.
			 */
			assert($item->count() === 1);
			
			return new RelationshipContent(true, $item);
		});
		
		return $_return;
	}
	
	public function startQueryBuilder(): QueryBuilder
	{
		$parent = $this->getField()->getModel();
		assert($parent->getActiveRecord() !== null);
		
		$query = $this->getReferenced()->getModel()->query();
		$query->where(
			$this->getReferenced()->getName(),
			$parent->get($this->getField()->getName())
		);
		
		return $query;
	}
	
	public function injector(): RelationshipInjectorInterface
	{
		return new DirectRelationshipInjector($this->getField(), $this->getReferenced());
	}
}
