<?php namespace spitfire\model\query;

use BadMethodCallException;
use spitfire\model\Model;
use spitfire\model\QueryBuilderInterface;
use spitfire\model\relations\BelongsToOne;
use spitfire\model\relations\HasMany;
use spitfire\model\relations\Relationship;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\query\RestrictionGroup;

class ExtendedRestrictionGroupBuilder extends RestrictionGroupBuilder
{
	
	private $query;
	
	public function __construct(QueryBuilderInterface $queryBuilder, RestrictionGroup $restrictionGroup)
	{
		$this->query = $queryBuilder;
		
		parent::__construct(
			$this->query->getQuery()->getFrom()->output(),
			$restrictionGroup
		);
	}
	
	public function where(...$args): RestrictionGroupBuilder
	{
		switch (count($args)) {
			case 2:
				$field = $args[0];
				$operator = '=';
				$value = $args[1];
				break;
			case 3:
				$field = $args[0];
				$operator = $args[1];
				$value = $args[2];
				break;
			default:
				throw new BadMethodCallException('Invalid argument count for where', 2202231731);
		}
		
		if ($value instanceof Model) {
			assert(method_exists($this->query->getModel(), $field));
			
			$relation = $this->query->getModel()->{$field}();
			
			/**
			 * The relationship MUST be a relationship. Otherwise we're running into
			 * issues.
			 */
			assert($relation instanceof Relationship);
			
			/**
			 * Only local queries can be performed. And only for DIRECT relations like belongsToOne
			 * or hasMany. Otherwise, we would need join logic, that we do not have yet.
			 *
			 * @todo In the future this could be moved to the injectors where they could make complex
			 * joins or similar to create a query for any kind of relationship.
			 */
			assert($relation->getField()->getModel() === $this->query->getModel());
			assert($relation instanceof BelongsToOne || $relation instanceof HasMany);
			
			return parent::where(
				$relation->getField()->getName(),
				$operator,
				$value->getActiveRecord()->get($relation->getReferenced()->getName())
			);
		}
		else {
			return parent::where(...$args);
		}
	}
	
	/**
	 *
	 * @todo These methods imply that only a querybuilder can use them
	 * @param string $relation
	 * @param callable(RestrictionGroupBuilderInterface):void|null $body
	 */
	public function has(string $relation, callable $body = null) : self
	{
		
		$relation = $this->query->getModel()->{$relation}();
		assert($relation instanceof RelationshipInterface);
		
		$relation->injector()->existence($this, $body);
		
		return $this;
	}
	
	/**
	 *
	 * @param string $relation
	 * @param callable(RestrictionGroupBuilderInterface):void|null $body
	 */
	public function hasNo(string $relation, callable $body = null) : self
	{
		
		$relation = $this->query->getModel()->{$relation}();
		assert($relation instanceof RelationshipInterface);
		
		$relation->injector()->absence($this, $body);
		
		return $this;
	}
	
	public function context() : QueryBuilderInterface
	{
		return $this->query;
	}
}
