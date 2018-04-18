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
			$qt = $this->getValue()->getRedirection()? $this->getValue()->getRedirection()->getQueryTable() : $this->getValue()->getQueryTable();
			$_ret[] = sprintf($this->getOperator() === '='? '%s IS NOT NULL' : '%s IS NULL', $of->queryFieldInstance($qt, $field));
		}
		
		$_ret = array_merge($_ret, $this->getValue()->getCompositeRestrictions()->toArray());
		
		/**
		 * Check the operator and return the appropriate SQL for the driver to run
		 * the query.
		 */
		return sprintf('(%s)', implode(' AND ', $_ret));
	}
	
}