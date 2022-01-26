<?php namespace spitfire\storage\database;

use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;

/**
 * A subquery allows the application to wrap a query, allowing a join to
 * use it as a source of data. The subquery will generate a table reference
 * that can be used to reference the output of the subquery.
 */
class Subquery
{
	
	/**
	 * In order to prevent queries from clashing with each other, we use a counter
	 * to give each subquery a unique number.
	 * 
	 * @var int
	 */
	private static $counter = 1;
	
	/**
	 * The query providing data. This is used to extract the outputs and will be stringified
	 * into the SQL.
	 * 
	 * @var Query
	 */
	private $query;
	
	/**
	 * The table we extracted from the query. Please note that this is created in the constructor,
	 * which means that changes to the query won't be reflected.
	 * 
	 * @var TableReference
	 */
	private $table;
	
	/**
	 * Instances a new subquery.
	 * 
	 * @param Query $query
	 */
	public function __construct(Query $query)
	{
		$this->query = $query;
		$this->table = new TableReference('sq_' . $query->getTable()->getName() . self::$counter++, $query->getOutputs());
	}
	
	/**
	 * Returns the table that represents the output of the subquery.
	 * 
	 * @return TableReference
	 */
	public function getTable() : TableReference
	{
		return $this->table;
	}
	
	/**
	 * Returns the query where the data for the subquery is coming from.
	 * 
	 * @return Query
	 */
	public function getQuery() : Query
	{
		return $this->query;
	}
}
