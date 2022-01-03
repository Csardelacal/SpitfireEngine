<?php namespace spitfire\storage\database;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\query\OutputObjectInterface;

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
class QueryField implements OutputObjectInterface
{
	/** 
	 * The actual database field.
	 * 
	 * @todo This feels slightly 'overlinked', since the field only provides us with it's name
	 * in this context, and we do not need access to information like it's type, whether
	 * it's nullable or what table it belongs to. 
	 * 
	 * @var Field
	 */
	private $field;
	
	/**
	 * The query-table we're working with. This allows the field to know which 
	 * table alias it belongs to.
	 *
	 * @var QueryTable
	 */
	private $table;
	
	/**
	 * Instances a new Queryfield. This object scopes a field to a certain query,
	 * which makes it very versatile for referencing fields accross queries in 
	 * join operations.
	 * 
	 * @param QueryTable $table
	 * @param Field $field
	 */
	public function __construct(QueryTable$table, Field $field) {
		$this->table = $table;
		$this->field = $field;
	}
	
	/**
	 * Returns the parent Table for this field. 
	 * 
	 * @return QueryTable
	 */
	public function getTable() : QueryTable 
	{
		return $this->table;
	}
	
	/**
	 * Returns the source field for this object.
	 * 
	 * @return Field
	 */
	public function getField() : Field 
	{
		return $this->field;
	}
	
	/**
	 * Returns the name of the field that underlies the queryfield.
	 * 
	 * @return string
	 */
	public function getName() : string
	{
		return $this->field->getName();
	}
	
	/**
	 * Fields generally don't need aliases, since we can just reference them by the table
	 * name and their name and fields cannot be duplicated within a table.
	 * 
	 * @return string|null 
	 */
	public function getAlias():? string
	{
		return null;
	}
	
	/**
	 * Returns an array of fields that compose the physical components of the 
	 * field. This method automatically converts the fields to QueryField so they
	 * can be used again.
	 * 
	 * @deprecated since v0.2 20210812 This is broken, do not use
	 * @return Field[]
	 */
	public function getPhysical() : array {
		throw new ApplicationException('Broken method getPhysical was invoked');
	}
}
