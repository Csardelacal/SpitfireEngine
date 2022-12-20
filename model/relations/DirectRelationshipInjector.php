<?php namespace spitfire\model\relations;

use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\query\ExtendedRestrictionGroupBuilder;
use spitfire\model\query\RestrictionGroupBuilderInterface;
use spitfire\model\QueryBuilder;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\RestrictionGroup;

/**
 * Direct relationships are usually the BelongsToOne or HasMany kind of. Querying for
 * existence or absence, simply means looking up whether the remote (referenced) table
 * contains a record that matches what we established.
 * 
 * @template LOCAL of Model
 * @template REMOTE of Model
 * @implements RelationshipInjectorInterface<REMOTE>
 */
class DirectRelationshipInjector implements RelationshipInjectorInterface
{
	
	/**
	 * 
	 * @var Field<LOCAL>
	 */
	private Field $field;
	
	/**
	 * 
	 * @var Field<REMOTE>
	 */
	private Field $referenced;
	
	
	/**
	 * 
	 * @param Field<LOCAL> $field
	 * @param Field<REMOTE> $referenced
	 */
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
				$payload($builder->getRestrictions());
				
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
				$payload($builder->getRestrictions());
				
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
}
