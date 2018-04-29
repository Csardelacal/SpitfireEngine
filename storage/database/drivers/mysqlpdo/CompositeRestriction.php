<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use spitfire\exceptions\PrivateException;
use spitfire\storage\database\CompositeRestriction as ParentClass;
use spitfire\storage\database\drivers\MysqlPDOQuery;
use spitfire\storage\database\RestrictionGroup;

class CompositeRestriction extends ParentClass
{
	
	public function makeSimpleRestrictions() {
		
		$field = $this->getField();
		$value = $this->getValue();
		$of    = $this->getQuery()->getTable()->getDb()->getObjectFactory();
		
		/*
		 * Extract the primary fields for the remote table so we can indicate to the
		 * database whether they should be null or not.
		 * 
		 * Please note that we will always use "IS NOT NULL" so the connectors stay
		 * consistent with the rest of the restrictions
		 */
		$fields = $this->getValue()->getQueryTable()->getTable()->getPrimaryKey()->getFields();
		$group  = $of->restrictionGroupInstance($this->getParent());
		
		/*
		 * Loop over the fields and put them in an array so it can be concatenated
		 * before being returned.
		 */
		foreach($fields as $field) {
			$qt = $this->getValue()->getRedirection()? $this->getValue()->getRedirection()->getQueryTable() : $this->getValue()->getQueryTable();
			$group->push($of->restrictionInstance($group, $of->queryFieldInstance($qt, $field), null, $this->getOperator() === '='? 'IS NOT' : 'IS'));
		}
		
		return $group;
	}
	
	public function __toString() {
		$field = $this->getField();
		$value = $this->getValue();
		$of    = $this->getQuery()->getTable()->getDb()->getObjectFactory();
		
		if ($field === null || $value === null) {
			throw new PrivateException('Deprecated: Composite restrictions do not receive null parameters', 2801191504);
		} 
		
		try {
		return strval($this->makeSimpleRestrictions());
		} catch (\Throwable$e) {
			die($e->getTraceAsString());
		}
	}
	
}