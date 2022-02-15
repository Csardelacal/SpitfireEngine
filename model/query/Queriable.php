<?php namespace spitfire\model\query;

use spitfire\storage\database\Query;

trait Queriable
{
	
	public function where(...$args) : Query
	{
		return $this->getQuery()->where(...$args);
	}
	
	abstract public function getQuery() : Query;
}
