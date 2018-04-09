<?php namespace spitfire\storage\database;

use spitfire\model\Field as Logical;
use spitfire\Model;

class CompositeRestriction
{
	private $parent;
	private $field;
	private $value;
	private $operator;
	
	public function __construct(RestrictionGroup$parent, Logical$field = null, $value = null, $operator = Restriction::EQUAL_OPERATOR) {
		
		if ($value instanceof Model) { $value = $value->getQuery(); }
		if ($value instanceof Query) { $value->setAliased(true); }
		
		$this->parent = $parent;
		$this->field = $field;
		$this->value = $value;
		$this->operator = $operator;
	}
	
	/**
	 * 
	 * @return Query
	 */
	public function getQuery() {
		return $this->parent? $this->parent->getQuery() : null;
	}
	
	/**
	 * 
	 * @return RestrictionGroup
	 */
	public function getParent() {
		return $this->parent;
	}

	public function setQuery(Query$query) {
		$this->parent = $query;
	}

	public function setParent(RestrictionGroup$query) {
		$this->parent = $query;
	}

	public function getField() {
		return $this->field;
	}

	public function setField(Logical$field) {
		$this->field = $field;
	}
	
	/**
	 * 
	 * @return Query
	 */
	public function getValue() {
		if ($this->value instanceof Model) { $this->value = $this->value->getQuery(); }
		return $this->value;
	}

	public function setValue($value) {
		$this->value = $value;
	}

	public function getOperator() {
		return $this->operator === null? '=' : $this->operator;
	}

	public function setOperator($operator) {
		$this->operator = $operator;
	}
	
	public function getPhysicalSubqueries() {
		$field     = $this->getField();
		$value     = $this->getValue();
		$of        = $this->getQuery()->getTable()->getDb()->getObjectFactory();
		$connector = $field->getConnectorQueries($this->getQuery());
		
		if ($field === null || $value === null) {
			throw new PrivateException('Deprecated: Composite restrictions do not receive null parameters', 2801191504);
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
				$c->filterCompositeRestrictions();
				$c->filterEmptyGroups();
				
				$c->isEmpty() || $group->push($c);
			}
			elseif ($r instanceof CompositeRestriction) {
				//Do nothign
			}
			else {
				$group->push($r);
			}
		}
		
		$last      = end($connector);
		$last->setId($this->getValue()->getId());
		
		/*
		 * Once we looped over the sub restrictions, we can determine whether the
		 * additional group is actually necessary. If it is, we add it to the output
		 */
		if (!$group->isEmpty()) {
			$last->push($group);
		}
		
		/*
		 * Since layered composite restrictions cannot be handled in the same way
		 * as their "higher" counterparts we need to reorganize the restrictions
		 * for subsqueries of subqueries.
		 * 
		 * Basically, in higher levels we indicate that the top query should either
		 * include or not the lower levels. This is not supported on tables that 
		 * get joined.
		 * 
		 * This currently causes a redundant restrictions to appear, but these shouldn't
		 * harm the operation as it is.
		 * 
		 */
		$subqueries = $this->getValue()->getPhysicalSubqueries();
		
		return array_merge($connector, $subqueries); 
	}
	
}
