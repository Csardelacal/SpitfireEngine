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
			$fields = $this->getValue()->getQueryTable()->getTable()->getPrimaryKey()->getFields();
			$_ret   = Array();
			
			foreach($fields as $field) {
				$f = $this->getValue()->queryFieldInstance($field);
				$o = 'IS NOT';
				$_ret[] = "{$f} {$o} NULL";
			}
			
			/**
			 * 
			 * @var MysqlPDOQuery The query
			 */
			$value = $this->getValue();
			$group = $this->getQuery()->getTable()->getDb()->getObjectFactory()->restrictionGroupInstance($this->getQuery());
			
			/**
			 * The system needs to create a copy of the subordinated restrictions 
			 * (without the simple ones) to be able to syntax a proper SQL query.
			 * 
			 * @todo Refactor this to look proper
			 */
			foreach ($value as $r) {
				if ($r instanceof \spitfire\storage\database\Restriction) { $c = $r; }
				if ($r instanceof CompositeRestriction) { $c = $r; }
				if ($r instanceof \spitfire\storage\database\RestrictionGroup) { 
					$c = clone $r; 
					$c->filterEmptyGroups();
				}
				
				if (!$c instanceof \spitfire\storage\database\RestrictionGroup || !$c->isEmpty()) {
					$group->push($c);
				}
			}
			
			if (!$group->isEmpty()) {
				$_ret[] = $group;
			}
			
			if ($this->getOperator() === '=') {
				return sprintf('(%s)', implode(' AND ', array_filter($_ret)));
			}
			else {
				return sprintf('NOT(%s)', implode(' AND ', array_filter($_ret)));
			}
		}
	}
	
}