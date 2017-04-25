<?php namespace spitfire\storage\database;

use spitfire\model\Field as Logical;

abstract class QueryField
{
	/** @var Logical */
	private $field;
	/** @var Query */
	private $query;
	
	public function __construct(Query$query, $field) {
		$this->query = $query;
		$this->field = $field;
	}
	
	public function setQuery($query) {
		$this->query = $query;
	}

	/**
	 * @return Query
	 */
	public function getQuery() {
		return $this->query;
	}

	/**
	 * @return Logical
	 */
	public function getField() {
		return $this->field;
	}

	/**
	 * @return bool
	 */
	public function isLogical() {
		return $this->field instanceof Logical;
	}
	
	public function getPhysical() {
		if ($this->isLogical()) {
			$fields = $this->field->getPhysical();
			foreach ($fields as &$field) $field = $this->query->queryFieldInstance($field);
			unset($field);
			return $fields;
		}
	}
	
	abstract public function __toString();
}
