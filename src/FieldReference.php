<?php namespace spitfire\storage\database;

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
 */
class FieldReference
{
	/** 
	 * The actual database field.
	 * 
	 * @var string
	 */
	private $field;
	
	/**
	 * The query-table we're working with. This allows the field to know which 
	 * table alias it belongs to.
	 *
	 * @var TableReference
	 */
	private $table;
	
	/**
	 * Instances a new Queryfield. This object scopes a field to a certain query,
	 * which makes it very versatile for referencing fields accross queries in 
	 * join operations.
	 * 
	 * @param TableReference $table
	 * @param string $field
	 */
	public function __construct(TableReference $table, string $field) {
		$this->table = $table;
		$this->field = $field;
	}
	
	/**
	 * Returns the parent Table for this field. 
	 * 
	 * @return TableReference
	 */
	public function getTable() : TableReference 
	{
		return $this->table;
	}
	
	/**
	 * Returns the name of the field that underlies the queryfield.
	 * 
	 * @return string
	 */
	public function getName() : string
	{
		return $this->field;
	}
	
}
