<?php namespace spitfire\model\relations;

use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\model\query\Queriable;
use spitfire\model\QueryBuilder;

/**
 * The belongsTo relationship allows an application to indicate that this
 * model is part of a 1:n relationship with another model.
 *
 * In this case, the model using this relationship is the n part or the
 * child. This makes it a single relationship, since models using this
 * relationship will have a single parent.
 */
class HasMany extends Relationship implements RelationshipMultipleInterface
{
	
	public function getQuery(): QueryBuilder
	{
		$query = $this->getReferenced()->getModel()->query();
		
		$query->getQuery()->where(
			$query->getQuery()->getFrom()->output()->getOutput($this->getReferenced()->getField()),
			$this->getField()->getModel()->getPrimaryData()
		);
		
		return $query;
	}
	
	public function injector(): RelationshipInjectorInterface
	{
		
	}
}
