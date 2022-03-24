<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\Model;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSetMapping;
use spitfire\model\QueryBuilder;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\JoinTable;

/**
 */
class BelongsToMany extends Relationship implements RelationshipMultipleInterface
{
	
	/**
	 *
	 * @var Model
	 */
	private $pivot;
	
	/**
	 * 
	 * @var string
	 */
	private $local;
	
	/**
	 * @var string
	 */
	private $remote;
	
	public function using(Model $model, string $local = null, string $remote = null)
	{
		$this->pivot = $model;
		$this->local = $local;
		$this->remote = $remote;
	}
	
	public function buildQuery(Collection $parents) : QueryBuilder
	{
		
		$query = $this->getReferenced()->getModel()->query();
		
		/**
		 * Find the layouts for the tables we need to use to operate the 
		 * relationship. In belongsToMany (or many-to-many) this means that
		 * there's three tables involved.
		 */
		$tablePivot = $this->pivot->getTable();
		$tableLocal = $this->getField()->getModel()->getTable();
		$tableReferenced = $this->getReferenced()->getModel()->getTable();
		
		$pivotLocal = $this->local?: sprintf(
			'%s%s',
			$tableLocal->getTableName(),
			$tableLocal->getPrimaryKey()->getFields()->first()->getName()
		);
		
		$pivotRemote = $this->remote?: sprintf(
			'%s%s',
			$tableReferenced->getTableName(),
			$tableReferenced->getPrimaryKey()->getFields()->first()->getName()
		);
		
		/**
		 * If we pivot on ourselves we're creating a scenario we do not want. This commonly
		 * happens if the model is in an m-n relationship to itself.
		 */
		assert($pivotLocal !== $pivotRemote);
		
		$pivot = $query->getQuery()->joinTable(
			$tablePivot->getTableReference(),
			function (JoinTable $pivot, DatabaseQuery $remote) use ($pivotRemote, $pivotLocal, $parents) {
				/**
				 * This is the primary key of the schema we're connecting to.
				 */
				$rpk = $this->getReferenced()->getModel()->getTable()->getPrimaryKey()->getFields()->first()->getName();
				
				$pivot->on(
					$pivot->getOutput($pivotRemote),
					$remote->getFrom()->output()->getOutput($rpk)
				);
				
				/**
				 * Create an or group and loop over the parents to build a query with all the
				 * required parents.
				 */
				$pivot->group('OR', function (RestrictionGroupBuilder $group) use ($parents, $pivot, $pivotLocal) {
					foreach ($parents as $parent) {
						$group->where(
							$pivot->getOutput($pivotLocal),
							$parent->getPrimaryData()
						);
					}
				});
			}
		);
		
		$query->getQuery()->where($pivot->getOutput($pivotLocal), '!=', null);
		
		/**
		 * Select the pivot fields into the query result
		 */
		$map = new ResultSetMapping($this->pivot);
		
		foreach ($tablePivot->getFields() as $field) {
			$_field = $pivot->getAlias()->output()->getOutput($field->getName());
			$query->getQuery()->selectField($_field);
		}
		
		return $query->withMapping(
			new ResultSetMapping(
				$this->pivot,
				$map
			)
		);
	}
	
	/**
	 *
	 */
	public function getQuery(): QueryBuilder
	{
		return $this->buildQuery(new Collection([$this->getField()->getModel()]));
	}
	
	public function injector(): RelationshipInjectorInterface
	{
		return new BelongsToManyRelationshipInjector($this);
	}
}
