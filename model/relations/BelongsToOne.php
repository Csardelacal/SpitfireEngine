<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\QueryBuilder;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\Join;
use spitfire\storage\database\query\JoinTable;

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
					$this->query->getQuery()->getFrom()->output()->getOutput($this->getReferenced()->getField()),
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
	
	public function eagerLoad(Collection $parents): Collection
	{
		return $this->buildQuery($parents)->all();
	}
	
	public function injector(): RelationshipInjectorInterface
	{
		return new BelongsToOneRelationshipInjector($this->getField(), $this->getReferenced());
	}
}
