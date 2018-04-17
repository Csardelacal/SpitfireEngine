<?php namespace spitfire\storage\database\drivers\sql;

use spitfire\storage\database\Query;

abstract class SQLQuery extends Query
{
	
	
	/**
	 * It retrieves all the subqueries that are needed to be executed on a relational
	 * DB before the main query.
	 * 
	 * We could have used a single method with a flag, but this way seems cleaner
	 * and more hassle free than otherwise.
	 * 
	 * @return Query[]
	 */
	public function makeExecutionPlan() {
		$copy      = clone $this;
		$_return   = [];
		$queries   = $copy->getSubqueries();
		
		/*
		 * Inject the current query into the array. The data for this query needs
		 * to be retrieved last.
		 */
		$queries[] = $this;
		
		foreach ($queries as $query) {
			$_return   = array_merge($_return, $query->normalize()->physicalize());
			$_return[] = $query;
		}
		
		return array_reverse($_return);
	}
	
}
