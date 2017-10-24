<?php namespace spitfire\storage\database\drivers;

use \spitfire\storage\database\CompositeRestriction;

class MysqlPDOCompositeRestriction extends CompositeRestriction
{
	
	public function __toString() {
		//TODO: This should just print the PK IS / IS NOT NULL
		$field = $this->getField();
		$value = $this->getValue();
		
		if ($field === null || $value === null) {
			return implode(' AND ', $this->getSimpleRestrictions());
		} 
		else {
			$fields = $this->getValue()->getQueryTable()->getTable()->getPrimaryKey();
			$_ret   = Array();
			
			foreach($fields as $field) {
				$f = $this->getValue()->queryFieldInstance($field);
				$o = $this->getOperator() === '='? 'IS NOT' : 'IS';
				$_ret[] = "{$f} {$o} NULL";
			}
			
			/**
			 * 
			 * @var MysqlPDOQuery The query
			 */
			$value = $this->getValue();
			/**
			 * 
			 * @todo The object factory doesn't provide this method
			 */
			$group = $this->getQuery()->getTable()->getTable()->getDb()->getObjectFactory()->restrictionGroupInstance($this->getQuery());
			foreach ($value as $r) {
				$_ret[] = $r;
			}
			
			return sprintf('(%s)', implode(' AND ', $_ret));
		}
	}
	
}