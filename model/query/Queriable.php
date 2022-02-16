<?php namespace spitfire\model\query;

use spitfire\model\Model;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\Query;

trait Queriable
{
	
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
		}
		
		if ($value instanceof Model) {
			$relation = $this->getModel()->{$field}();
			assert($relation instanceof RelationshipInterface);
			
			$relation->injectWhere($this->getQuery(), $value);
			$this->getQuery();
			return $this;
		}
		
		$table = $this->getQuery()->getFrom()->output();
		$this->getQuery()->where($table->getOutput($field), $operator, $value);
		return $this;
	}
	
	public function whereHas($relation, $value) : Query
	{
		
		$relation = $this->getModel()->{$relation}();
		assert($relation instanceof RelationshipInterface);
		
		$relation->injectWhereHas($this->getQuery(), $value);
		return $this->getQuery();
	}
	
	abstract public function getQuery() : Query;
	
	abstract public function getModel() : Model;
}
