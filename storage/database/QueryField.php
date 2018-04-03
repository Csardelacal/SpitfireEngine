<?php namespace spitfire\storage\database;

use spitfire\model\Field as Logical;

/**
 * The query field object is a component that allows a Query to wrap a field and
 * connect it to itself. This is important for the DBA since it allows the app
 * to establish connections between the different queries when assembling SQL
 * or similar.
 * 
 * When a query is connected to a field, you may use this to establish relationships
 * and create complex queries that can properly be joined.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 * @abstract
 */
abstract class QueryField
{
	/** 
	 * The actual database field. Note that this field is 
	 * 
	 * @var Logical 
	 */
	private $field;
	
	/**
	 *
	 * @var QueryTable
	 */
	private $table;
	
	public function __construct(QueryTable$table, $field) {
		$this->table = $table;
		$this->field = $field;
	}
	
	public function getQueryTable() {
		return $this->table;
	}

	/**
	 * @return Logical
	 */
	public function getField() {
		return $this->field;
	}
	
	public function getPhysical() {
		/*
		 * Get the object factory for the current DB connection. It is then used 
		 * to create physical copies of logical fields.
		 */
		$of = $this->table->getTable()->getDb()->getObjectFactory();
		
		if ($this->field instanceof Logical) {
			$fields = $this->field->getPhysical();
			
			foreach ($fields as &$field) {
				$field = $of->queryFieldInstance($this->table, $field);
			}
			
			return $fields;
		}
		
		return [$of->queryFieldInstance($this->table, $this->field)];
	}
	
	abstract public function __toString();
}
