<?php namespace spitfire\model\relations;

use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
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
class BelongsTo extends Relationship implements RelationshipSingleInterface
{
	
	/**
	 * If the query wishes to find items that belong to another model, all we
	 * have to do is look for those where the referencing field matches the
	 * id of the parent model.
	 *
	 * @param DatabaseQuery $query
	 * @param Model $model
	 * @return void
	 */
	public function injectWhere(DatabaseQuery $query, Model $model) : void
	{
		$table = $this->getField()->getModel()->getTable()->getTableReference();
		$query->where($table->getOutput($this->getField()->getField()), $model->getPrimaryData());
	}
	
	/**
	 * When a query wishes to import the children eagerly, the application needs to
	 * perform a join operation and read the data.
	 *
	 * @param DatabaseQuery $query
	 * @param callable $fn
	 * @return Join
	 */
	public function injectWith(DatabaseQuery $query, callable $fn) : Join
	{
		return $query->joinTable(
			$this->getField()->getModel()->getTable()->getTableReference(),
			function (JoinTable $t, DatabaseQuery $parent) use ($fn) : void {
				
				$primary = $this->getReferenced()->getModel()->getTable()->getPrimaryKey();
				
				$t->on(
					$t->getAlias()->output()->getOutput($this->getField()->getField()),
					$parent->getFrom()->output()->getOutput($primary->getFields()->first()->getName())
				);
				
				$fn($t);
			}
		);
	}
	
	/**
	 * Allows the user to query for elements that match a certain condition in their remote
	 * counterpart. This could be used to, for example, to select the posts by users that
	 * have verified their email something like this:
	 *
	 * PostModel::query()->whereHas('user', function (Query $query) { $query->where('verified', true); });
	 *
	 * @param DatabaseQuery $query
	 * @param callable(Query):void $fn
	 * @return void
	 */
	public function injectWhereHas(DatabaseQuery $query, callable $fn) : void
	{	
		$query->whereExists(function (TableIdentifierInterface $parent) use ($fn) : DatabaseQuery {
			$subquery = $this->getReferenced()->getModel()->query();
			$fn($subquery);
			
			$query = $subquery->getQuery();
			$query->select($this->getReferenced()->getField());
			$query->where(
				$query->getFrom()->output()->getOutput($this->getReferenced()->getField()),
				$parent->getOutput($this->getReferenced()->getModel()->getTable()->getPrimaryKey()->getFields()->first()->getName())
			);
			
			return $query;
		});
	}
}
