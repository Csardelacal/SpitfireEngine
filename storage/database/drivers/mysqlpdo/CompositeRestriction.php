<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use spitfire\exceptions\PrivateException;
use spitfire\storage\database\CompositeRestriction as ParentClass;
use spitfire\storage\database\drivers\MysqlPDOQuery;
use spitfire\storage\database\RestrictionGroup;

class CompositeRestriction extends ParentClass
{
	
	public function __toString() {
		$field = $this->getField();
		$value = $this->getValue();
		$of    = $this->getQuery()->getTable()->getDb()->getObjectFactory();
		
		if ($field === null || $value === null) {
			throw new PrivateException('Deprecated: Composite restrictions do not receive null parameters', 2801191504);
		} 
		
		/*
		 * Extract the primary fields for the remote table so we can indicate to the
		 * database whether they should be null or not.
		 * 
		 * Please note that we will always use "IS NOT NULL" so the connectors stay
		 * consistent with the rest of the restrictions
		 */
		$fields = $this->getValue()->getQueryTable()->getTable()->getPrimaryKey()->getFields();
		$_ret   = Array();
		
		/*
		 * Loop over the fields and put them in an array so it can be concatenated
		 * before being returned.
		 */
		foreach($fields as $field) {
			$_ret[] = sprintf('%s IS NOT NULL', $of->queryFieldInstance($this->getValue()->getQueryTable(), $field));
		}

		/**
		 * 
		 * @var MysqlPDOQuery The query
		 */
		$group = $of->restrictionGroupInstance($this->getQuery(), RestrictionGroup::TYPE_AND);

		/**
		 * The system needs to create a copy of the subordinated restrictions 
		 * to be able to syntax a proper SQL query.
		 * 
		 * @todo Refactor this to look proper
		 */
		foreach ($value as $r) {
			if ($r instanceof RestrictionGroup) { 
				$c = clone $r; 
				$c->filterEmptyGroups();
				
				$c->isEmpty() || $group->push($c);
			}
			else {
				$group->push($r);
			}
		}
		
		/*
		 * Once we looped over the sub restrictions, we can determine whether the
		 * additional group is actually necessary. If it is, we add it to the output
		 */
		if (!$group->isEmpty()) {
			$_ret[] = $group;
		}
		
		/**
		 * Check the operator and return the appropriate SQL for the driver to run
		 * the query.
		 */
		return sprintf($this->getOperator() === '='? '(%s)' : 'NOT(%s)', implode(' AND ', $_ret));
	}
	
}