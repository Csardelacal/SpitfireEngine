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
			$group = $this->getQuery()->getTable()->getTable()->getDb()->getObjectFactory()->restrictionGroupInstance($this->getQuery());
			
			/**
			 * The system needs to create a copy of the subordinated restrictions 
			 * (without the simple ones) to be able to syntax a proper SQL query.
			 * 
			 * @todo Refactor this to look proper
			 */
			foreach ($value as $r) {
				if ($r instanceof \spitfire\storage\database\Restriction) { continue; }
				if ($r instanceof CompositeRestriction) { $c = $r; }
				if ($r instanceof \spitfire\storage\database\RestrictionGroup) { 
					$c = clone $r; 
					$w = $c->filter(function ($e) { return !$e instanceof \spitfire\storage\database\Restriction; }); 
					$c->reset()->add($w->toArray());
				}
				
				$group->push($c);
			}
			
			return sprintf('(%s)', implode(' AND ', $_ret));
		}
	}
	
}