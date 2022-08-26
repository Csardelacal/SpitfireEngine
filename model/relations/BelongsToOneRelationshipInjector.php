<?php namespace spitfire\model\relations;

use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\query\ExtendedRestrictionGroupBuilder;
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
		
		assert($this->field->getName() !== null);
		assert($this->referenced->getName() !== null);
	}
	
	/**
	 *
	 * @param ExtendedRestrictionGroupBuilder $restrictions
	 * @param callable(RestrictionGroupBuilderInterface):void|null $payload
	 */
	public function existence(ExtendedRestrictionGroupBuilder $restrictions, ?callable $payload = null): void
	{
		
		/**
		 * Create a subquery that will link the table we're querying with the table we're referencing.
		 * The user provided closure can write restrictions into that query.
		 */
		$restrictions->getDBRestrictions()
			->whereExists(function (TableIdentifierInterface $table) use ($payload): DatabaseQuery {
				$model = $this->referenced->getModel();
				$builder = new QueryBuilder($model);
				$payload($builder);
				
				/**
				 * Create the restriction that filters the second table by connecting it to the first one.
				 * This is the part that generates the ON table.ref_id = referenced._id
				 */
				$builder->getRestrictions()->where(
					$this->referenced->getName(),
					$table->getOutput($this->field->getName())
				);
				
				$query = $builder->getQuery();
				$query->selectField($query->getFrom()->output()->getOutput($this->referenced->getName()));
				return $query;
			});
	}
	
	/**
	 *
	 * @param ExtendedRestrictionGroupBuilder $restrictions
	 * @param callable(RestrictionGroupBuilderInterface):void|null $payload
	 */
	public function absence(ExtendedRestrictionGroupBuilder $restrictions, ?callable $payload = null): void
	{
		
		/**
		 * Create a subquery that will link the table we're querying with the table we're referencing.
		 * The user provided closure can write restrictions into that query.
		 */
		$restrictions->getDBRestrictions()
			->whereNotExists(function (TableIdentifierInterface $table) use ($payload): DatabaseQuery {
				$model = $this->referenced->getModel();
				$builder = new QueryBuilder($model);
				$payload($builder);
				
				/**
				 * Create the restriction that filters the second table by connecting it to the first one.
				 * This is the part that generates the ON table.ref_id = referenced._id
				 */
				$builder->getRestrictions()->where(
					$this->referenced->getName(),
					$table->getOutput($this->field->getName())
				);
				
				$query = $builder->getQuery();
				$query->selectField($query->getFrom()->output()->getOutput($this->referenced->getName()));
				return $query;
			});
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
