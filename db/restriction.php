<?php namespace spitfire\storage\database;

use spitfire\Model;
use spitfire\exceptions\PrivateException;

abstract class Restriction
{
	/** @var Query */
	private $query;
	
	/**
	 *
	 * @var QueryField
	 */
	private $field;
	private $value;
	private $operator;
	
	const LIKE_OPERATOR  = 'LIKE';
	const EQUAL_OPERATOR = '=';
	
	public function __construct($parent, $field, $value, $operator = '=') {
		if (is_null($operator)) $operator = self::EQUAL_OPERATOR;
		
		if (!$parent instanceof RestrictionGroup && $parent !== null)
			{ throw new PrivateException("A restriction's parent can only be a group"); }
		
		if ($value instanceof Model)
			$value = $value->getQuery();
		
		if (!$field instanceof QueryField)
			throw new PrivateException("Invalid field");
		
		$this->query    = $parent;
		$this->field    = $field;
		$this->value    = $value;
		$this->operator = trim($operator);
	}
	
	public function getTable(){
		return $this->field->getField()->getTable();
	}
	
	public function setTable() {
		throw new PrivateException('Deprecated');
	}
	
	public function getField() {
		return $this->field;
	}
	
	/**
	 * Returns the query this restriction belongs to. This allows a query to 
	 * define an alias for the table in order to avoid collissions.
	 * 
	 * @return \spitfire\storage\database\Query
	 */
	public function getQuery() {
		return $this->query->getQuery();
	}
	
	public function getParent() {
		return $this->query;
	}
	
	public function setParent($parent) {
		$this->query = $parent;
	}
	
	/**
	 * 
	 * @param Query $query
	 * @deprecated since version 0.1-dev 1604162323
	 */
	public function setQuery($query) {
		$this->query = $query;
		$this->field->setQuery($query);
	}
	
	public function getOperator() {
		if (is_array($this->value) && $this->operator != 'IN' && $this->operator != 'NOT IN') return 'IN';
		return $this->operator;
	}

	public function getValue() {
		return $this->value;
	}
	
	
	public function getPhysicalSubqueries() {
		return Array();
	}
	
	
	public function getSubqueries() {
		return Array();
	}
	
	public function getConnectingRestrictions() {
		return Array();
	}
	
	public function replaceQueryTable($old, $new) {
		
		if ($this->field->getQueryTable() === $old) {
			$this->field->setQueryTable($new);
		}
		
		if ($this->value instanceof QueryField && $this->value->getQueryTable() === $old) {
			$this->value->setQueryTable($new);
		}
	}
	
	public function negate() {
		switch ($this->operator) {
			case '=': 
				return $this->operator = '<>';
			case '<>': 
				return $this->operator = '=';
			case '>': 
				return $this->operator = '<';
			case '<': 
				return $this->operator = '>';
			case 'LIKE': 
				return $this->operator = 'NOT LIKE';
			case 'NOT LIKE': 
				return $this->operator = 'LIKE';
		}
	}
	
	/**
	 * Restrictions must be able to be casted to string. This is not only often
	 * necessary for many drivers to generate queries but also for debugging.
	 * 
	 * @return string
	 */
	abstract public function __toString();
}
