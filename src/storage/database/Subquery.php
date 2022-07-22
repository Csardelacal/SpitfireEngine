<?php namespace spitfire\storage\database;

use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\identifiers\TableIdentifier;
use spitfire\storage\database\query\SelectExpression;

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
	 * @var TableIdentifier
	 */
	private $table;
	
	/**
	 * Instances a new subquery.
	 *
	 * @param Query $query
	 */
	public function __construct(Query $query)
	{
		$outputs = $query->getOutputs()->each(function (SelectExpression $e) : string {
			return $e->getAlias();
		});
		
		$this->query = $query;
		$raw = $query->getTable()->raw();
		$this->table = new TableIdentifier(['sq_' . array_pop($raw) . self::$counter++], $outputs);
	}
	
	/**
	 * Returns the table that represents the output of the subquery.
	 *
	 * @return TableIdentifier
	 */
	public function getTable() : TableIdentifier
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
