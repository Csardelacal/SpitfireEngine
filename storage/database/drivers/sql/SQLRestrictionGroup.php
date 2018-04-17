<?php namespace spitfire\storage\database\drivers\sql;

use spitfire\storage\database\CompositeRestriction;
use spitfire\storage\database\RestrictionGroup;


class SQLRestrictionGroup
{
	
	
	public function physicalize() {
		$_ret = [];
		
		foreach ($this as $restriction) {
			if ($restriction instanceof RestrictionGroup) { 
				$_ret = array_merge($_ret, $restriction->physicalize()); 
			}
			
			elseif ($restriction instanceof CompositeRestriction) {
				$_ret = array_merge($_ret, $restriction->makeConnector());
			}
		}
		
		return $_ret;
	}
	
	public function isMixed() {
		$found = false;
		
		foreach ($this as $r) {
			if ($r instanceof RestrictionGroup && ($r->getType() !== $this->getType() || $r->isMixed())) {
				$found = true;
			}
		}
	}
}
