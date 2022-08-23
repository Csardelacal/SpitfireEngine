<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\Model;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\QueryBuilder;

/**
 * The belongsTo relationship allows an application to indicate that this
 * model is part of a 1:n relationship with another model.
 *
 * In this case, the model using this relationship is the n part or the
 * child. This makes it a single relationship, since models using this
 * relationship will have a single parent.
 */
class BelongsToOne extends Relationship implements RelationshipSingleInterface
{
	
	
	public function resolve(ActiveRecord $record): RelationshipContent
	{
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
			$record->get($this->getField()->getName())
		);
		
		/**
		 * We execute the query with all(), even though there may only be one record, but
		 * the idea behind it is that the Content needs a Collection anyway, and it allows
		 * us to healthcheck the system with assertions.
		 */
		$result = $query->all();
		
		assert($result->count() === 1);
		assert($result->first() instanceof ($this->getReferenced()->getModel()));
		
		return new RelationshipContent(true, $result);
	}
	
	public function buildQuery(Collection $parents) : QueryBuilder
	{
		$query = $this->getReferenced()->getModel()->query();
		
		/**
		 * Create an or group and loop over the parents to build a query with all the
		 * required parents.
		 */
		$query->group('OR', function (RestrictionGroupBuilder $group) use ($parents) {
			foreach ($parents as $parent) {
				$group->where(
					$this->getReferenced()->getField(),
					$parent->get($this->getField()->getField())
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
		return new BelongsToOneRelationshipInjector($this->getField(), $this->getReferenced());
	}
}
