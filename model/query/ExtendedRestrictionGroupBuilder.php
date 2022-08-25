<?php namespace spitfire\model\query;

use spitfire\model\QueryBuilderInterface;
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
