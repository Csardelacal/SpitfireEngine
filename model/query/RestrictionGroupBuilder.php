<?php namespace spitfire\model\query;

use BadMethodCallException;
use spitfire\model\Model;
use spitfire\model\QueryBuilder;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\query\RestrictionGroup;

class RestrictionGroupBuilder
{
	
	private $query;
	
	/**
	 *
	 * @var RestrictionGroup
	 */
	private $restrictionGroup;
	
	public function __construct(QueryBuilder $queryBuilder, RestrictionGroup $restrictionGroup)
	{
		$this->query = $queryBuilder;
		$this->restrictionGroup = $restrictionGroup;
	}
	
	
	public function where(...$args) : self
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
		
		$table = $this->query->getQuery()->getFrom()->output();
		$this->restrictionGroup->where($table->getOutput($field), $operator, $value);
		return $this;
	}
	
	/**
	 * 
	 * @todo These methods imply that only a querybuilder can use them
	 * @param string $relation
	 * @param callable(RestrictionGroupBuilder):void|null $body
	 */
	public function has(string $relation, callable $body = null) : self
	{
		
		$relation = $this->query->getModel()->{$relation}();
		assert($relation instanceof RelationshipInterface);
		
		$relation->injector()->existence(
			$this, 
			$body
		);
		
		return $this;
	}
	
	/**
	 * 
	 * @param string $relation
	 * @param callable(RestrictionGroupBuilder):void|null $body
	 */
	public function hasNo(string $relation, callable $body = null) : self
	{
		
		$relation = $this->query->getModel()->{$relation}();
		assert($relation instanceof RelationshipInterface);
		
		$relation->injector()->absence($this, $body);
		
		return $this;
	}
}
