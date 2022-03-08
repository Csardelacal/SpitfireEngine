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
		
		if ($value instanceof Model) {
			$relation = $this->query->getModel()->{$field}();
			assert($relation instanceof RelationshipInterface);
			
			$relation->injector()->injectWhere($this->restrictionGroup, $value);
			return $this;
		}
		
		$table = $this->query->getQuery()->getFrom()->output();
		$this->restrictionGroup->where($table->getOutput($field), $operator, $value);
		return $this;
	}
	
	public function whereHas($relation, $value) : RestrictionGroupBuilder
	{
		
		$relation = $this->query->getModel()->{$relation}();
		assert($relation instanceof RelationshipInterface);
		
		$relation->injector()->injectWhereHas($this->restrictionGroup, $value);
		return $this;
	}
	
}