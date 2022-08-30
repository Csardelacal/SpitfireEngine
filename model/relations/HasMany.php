<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\Model;
use spitfire\model\query\ExtendedRestrictionGroupBuilder;
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
 */
class HasMany extends Relationship implements RelationshipInterface
{
	
	public function resolve(ActiveRecord $record): RelationshipContent
	{
		
		/**
		 * Start by querying the referenced model. This is the model for which we
		 * wish to return data.
		 */
		$query = $this->getReferenced()->getModel()->query();
		
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
		assert($result->isEmpty() || $result->first() instanceof ($this->getReferenced()->getModel()));
		
		return new RelationshipContent(false, $result);
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
				assert($record->getModel() instanceof ($this->getField()->getModel()));
				
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
			assert($item instanceof ($this->getReferenced()->getModel()));
			
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
	
	public function buildQuery(Collection $parents) : QueryBuilder
	{
		$query = $this->getReferenced()->getModel()->query();
		
		/**
		 * Create an or group and loop over the parents to build a query with all the
		 * required parents.
		 */
		$query->group('OR', function (ExtendedRestrictionGroupBuilder $group) use ($parents) {
			foreach ($parents as $parent) {
				$group->where(
					$this->getReferenced()->getName(),
					$parent->getPrimary()
				);
			}
		});
		
		return $query;
	}
	
	public function getQuery(): QueryBuilder
	{
		return $this->buildQuery(new Collection([$this->getField()->getModel()]));
	}
	
	/**
	 * Eagerly load the children of a relationship. Please note that this receives a collection of
	 * parents and returns a collection grouped by their ID.
	 *
	 * @param Collection<Model> $parents
	 * @return Collection<Collection<Model>>
	 */
	public function eagerLoad(Collection $parents): Collection
	{
		return $this->buildQuery($parents)->all()->groupBy(function (Model $e) {
			return $e->{$this->getReferenced()->getField()};
		});
	}
	
	public function injector(): RelationshipInjectorInterface
	{
		return new DirectRelationshipInjector($this->getField(), $this->getReferenced());
	}
}
