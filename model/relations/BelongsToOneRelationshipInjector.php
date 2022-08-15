<?php namespace spitfire\model\relations;

use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\QueryBuilder;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\RestrictionGroup;

class BelongsToOneRelationshipInjector implements RelationshipInjectorInterface
{
	
	private $field;
	
	private $referenced;
	
	
	public function __construct(Field $field, Field $referenced)
	{
		$this->field = $field;
		$this->referenced = $referenced;
		
		assert($this->field->getField() !== null);
		assert($this->referenced->getField() !== null);
	}
	
	
	/**
	 * If the query wishes to find items that belong to another model, all we
	 * have to do is look for those where the referencing field matches the
	 * id of the parent model.
	 *
	 * @param RestrictionGroup $query
	 * @param Model $model
	 * @return void
	 */
	public function injectWhere(DatabaseQuery $context, RestrictionGroup $query, Model $model) : void
	{
		$name = $this->field->getField();
		$query->where($context->getFrom()->output()->getOutput($name), $model->getPrimary());
	}
	
	/**
	 * Allows the user to query for elements that match a certain condition in their remote
	 * counterpart. This could be used to, for example, to select the posts by users that
	 * have verified their email something like this:
	 *
	 * PostModel::query()->whereHas('user', function (Query $query) { $query->where('verified', true); });
	 *
	 * @param RestrictionGroup $query
	 * @param callable(QueryBuilder):void $fn
	 * @return void
	 */
	public function injectWhereHas(RestrictionGroup $query, callable $fn) : void
	{
		$query->whereExists(function (TableIdentifierInterface $parent) use ($fn) : DatabaseQuery {
			/**
			 * The subquery is just a regular query being performed on the remote model (the
			 * one being referenced).
			 */
			$subquery = new QueryBuilder($this->referenced->getModel());
			
			/**
			 * We then let the developer apply any additional restrictions they whish to perform.
			 */
			$fn($subquery);
			
			/**
			 * After that, we need to access the underlying query that spitfire/database manages
			 * to inject the relation between the local and remote fields within the subquery.
			 */
			$query = $subquery->getQuery();
			$primary = $this->referenced->getField();
			
			$query->where(
				$query->getFrom()->output()->getOutput($this->referenced->getField()),
				$parent->getOutput($primary)
			);
			
			/**
			 *
			 */
			$query->select($this->referenced->getField());
			
			return $query;
		});
	}
}
