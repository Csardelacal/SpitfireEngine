<?php namespace spitfire\storage\database\drivers\sql;

use spitfire\storage\database\Query;

abstract class SQLQuery extends Query
{
	
	/**
	 * The redirection object is required only when assembling queries. Sometimes,
	 * a query has unmet dependencies that it cannot satisfy. In this case, it's 
	 * gonna copy itself and move all of it's restrictions to the new query.
	 * 
	 * This means that when serializing the query, the composite restriction should
	 * not print <code>old.primary IS NOT NULL</code> but <code>new.primary IS NOT NULL</code>.
	 * 
	 * But! When the parent injects the restrictions to connect the queries with 
	 * the parent, the old query must answer the call and assimilate them.
	 * 
	 * To achieve this behavior, I found it reasonable that the query introduces 
	 * a redirection property. When a composite restriction finds this, it will
	 * automatically use the target of the redirection.
	 * 
	 * NOTE: Composite queries do not follow multiple redirections.
	 *
	 * @var SQLQuery|null
	 */
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
		$of   = $this->getQuery()->getTable()->getDb()->getObjectFactory();
		
		foreach ($copy->getCompositeRestrictions() as $r) {
			
			$group = $of->restrictionGroupInstance($this, \spitfire\storage\database\RestrictionGroup::TYPE_AND);
			$group->push($r->getValue()->denormalize());
			$group->push($r);
			
			if ($r->getOperator() !== '=') {
				$group->negate();
			}
			
			$r->getParent()->push($group)->remove($r);
			$r->setParent($group);
			$copy->push($group);
		}
		
		$copy->normalize();
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
		$of        = $this->getQuery()->getTable()->getDb()->getObjectFactory();
		$group     = $of->restrictionGroupInstance($this, \spitfire\storage\database\RestrictionGroup::TYPE_AND);
		
		if ($this->isMixed() || $composite->isEmpty()) {
			return $group;
		}
		
		foreach ($composite as /*@var $r CompositeRestriction*/$r) {
			$sg = $of->restrictionGroupInstance($group, \spitfire\storage\database\RestrictionGroup::TYPE_AND);
			$sg->push($r->getValue()->denormalize()->setParent($sg));
			$r->getParent()->remove($r);
			$sg->push($r);
			
			if ($r->getOperator() !== '=') {
				$sg->negate();
			}
			
			$group->push($sg);
		}
		
		return $group;
	}
	
	/**
	 * 
	 * @return SQLQuery|null
	 */
	public function getRedirection() {
		return $this->redirection;
	}
	
	/**
	 * This is a driver specific method. If you're not exactly sure what a query
	 * redirection is, please avoid using this method.
	 * 
	 * @param SQLQuery|null $redirection
	 * @return $this
	 */
	public function setRedirection($redirection = null) {
		$this->redirection = $redirection;
		return $this;
	}
	
}
