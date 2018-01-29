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
	
	/**
	 * This method handles NULL scenarios.
	 * 
	 * This method simplifies complex restrictions when null values are involved.
	 * Usually, when querying you will define an equivalence between two values and
	 * launch the query. This method is called when that involves null.
	 * 
	 * You can either have a null value, which will force the database to check that
	 * the physical fields composing your logical field are null.
	 * 
	 * Or you can have a null field. Which will force the database to check that 
	 * one of the fields that this table has equals to the value you specified.
	 * 
	 * Please note that the usage of this function for other scenarios has been
	 * deprecated since 11/2014
	 * 
	 * 
	 * @deprecated since version 0.1-dev 20171115
	 * @return type
	 */
	public function getSimpleRestrictions() {
		
		trigger_error('CompositeRestriction::getSimpleRestrictions() is deprecated', E_USER_DEPRECATED);
		
		if ($this->field === null) {
			$table = $this->getQuery()->getTable();
			$fields = $table->getFields();
			$restrictions = $this->getQuery()->restrictionGroupInstance();
			
			foreach ($fields as $field) {
				if (!$field->getLogicalField() instanceof \Reference) {
					$restrictions->addRestriction($field, $this->getValue(), $this->operator);
				}
			}
			return Array($restrictions);
		}
		
		if ($this->value === null) {
			$restrictions = Array();
			foreach ($fields = $this->getField()->getPhysical() as $field) {
				$f = $this->getQuery()->queryFieldInstance($field);
				$v = null;
				$r = $this->getQuery()->restrictionInstance($f, $v, $this->operator);
				$restrictions[] = $r;
			}
			return $restrictions;
		}
	}
	
	
	public function getPhysicalSubqueries() {
		if ($this->field === null || $this->value === null) { return Array(); }
		
		$field     = $this->getField();
		$connector = $field->getConnectorQueries($this->getQuery());
		$last      = end($connector);
		
		$last->setId($this->getValue()->getId());
		
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
