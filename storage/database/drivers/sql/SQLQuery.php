<?php namespace spitfire\storage\database\drivers\sql;

use spitfire\storage\database\CompositeRestriction;
use spitfire\storage\database\Query;
use spitfire\storage\database\RestrictionGroup;

abstract class SQLQuery extends Query
{
	
	private $redirection = null;
	
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
		
		/*
		 * Inject the current query into the array. The data for this query needs
		 * to be retrieved last.
		 */
		$copy = clone $this;
		$_ret = $copy->physicalize(true);
		
		foreach ($_ret as $q) {
			if ($q === $copy) { continue; }
			$copy->add($q->denormalize());
		}
		
		return $_ret;
	}
	
	public function physicalize($top = false) {
		
		$copy = $this;
		$_ret = [$this];
		
		$composite = $copy->getCompositeRestrictions();
		
		foreach ($composite as $r) {
			
			$q = $r->getValue();
			$p = $q->physicalize();
			$c = $r->makeConnector();
			$_ret = array_merge($_ret, $c, $p);
		}
		
		if (!$top && $copy->isMixed() && !$composite->isEmpty()) {
			
			$clone = clone $copy;
			$of    = $copy->getTable()->getDb()->getObjectFactory();
			
			$clone->cloneQueryTable();
			$group = $of->restrictionGroupInstance($clone);
			
			foreach ($copy->getTable()->getPrimaryKey()->getFields() as $field) {
				$group->where(
					$of->queryFieldInstance($copy->getQueryTable(), $field),
					$of->queryFieldInstance($clone->getQueryTable(), $field)
				);
			}
			
			$copy->reset();
			$copy->setRedirection($clone);
			$clone->push($group);
			$_ret[] = $clone;
		}
		
		return $_ret;
	}
	
	public function denormalize() {
		
		$composite = $this->getCompositeRestrictions();
		
		if ($this->isMixed() || $composite->isEmpty()) {
			return [];
		}
		
		$_ret = [];
		
		foreach ($composite as $r) {
			$_ret = array_merge($_ret, [$r]);
		}
		
		$this->filterCompositeRestrictions();
		
		return $_ret;
	}
	
	public function getRedirection() {
		return $this->redirection;
	}
	
	public function setRedirection($redirection) {
		$this->redirection = $redirection;
		return $this;
	}
	
}
