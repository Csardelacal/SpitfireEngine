<?php namespace spitfire\storage\database;

use Closure;
use spitfire\collection\Collection;
use spitfire\storage\database\query\OutputObjectInterface;
use spitfire\storage\database\query\TableObjectInterface;

/**
 * The query provides a mechanism for assembling restrictions that Spitfire and
 * the DBMS driver can then convert into a SQL query (or similar, for NoSQL).
 * 
 * @todo The properties are protected, when they should actually be private
 */
class Query extends RestrictionGroup
{
	
	/** 
	 * The table this query is retrieving data from. This table is wrapped inside
	 * a QueryTable object to ensure that the table can refer back to the query
	 * when needed.
	 * 
	 * @var TableObjectInterface
	 */
	protected $table;
	
	/**
	 *
	 * @todo We should introduce a class that allows these queries to sort by multiple,
	 * and even layered (as in, in other queries) columns.
	 * @var OrderBy[]
	 */
	protected $order;
	
	/**
	 * Allows the query to include complex resultsets that accommodate more elaborate
	 * searches. A join is composed of a type (left, right, inner, outer) and a query
	 * that restricts it's return.
	 * 
	 * Please note, that this lowlevel library does not perform the logic for linking
	 * the query with the subquery (this needs to be performed by the developer or the
	 * model system).
	 * 
	 * @todo Introduce Join class
	 * @todo Introduce utility methods for the joins
	 * @var Collection<Join>
	 */
	private $joins;
	
	/**
	 * This contains an array of aggregation functions that are executed with the 
	 * query to provide metadata on the query.
	 * 
	 * @todo Provide a single output kind of type (something that can either refer to a field or a aggreation)
	 * @todo Rename to reflect the fact that this is what e expect as output of the query.
	 * @var OutputObjectInterface[]
	 */
	protected $calculated;
	
	/**
	 * Determines by which fields the result should be aggregated. This affects aggregation
	 * functions like count() or max(). Please note that, if the groupBy is populated, your
	 * application should only attempt to retrieve:
	 * 
	 * * Fields that are part of the groupby
	 * * Computed fields that reduce the result to a single record (like max or count)
	 *
	 * @var QueryField[]
	 */
	protected $groupBy = null;
	
	/**
	 * The number of records that should be skipped when working with the query's resultset.
	 * 
	 * @var int|null
	 */
	private $offset = null;
	
	/**
	 * The size of the largest resultset that this query should return. In record count.
	 * 
	 * @var int|null
	 */
	private $limit  = null;

	/** 
	 * 
	 * @param TableObjectInterface $table 
	 */
	public function __construct(TableObjectInterface $table) 
	{
		$this->table = $table;
		$this->joins = new Collection();
		
		#Initialize the parent
		parent::__construct(Array());
		$this->setType(RestrictionGroup::TYPE_AND);
	}
	
	/**
	 * Joins a table to the current query.
	 * 
	 * You can pass a second, optional argument to customize the join, this is a closure that
	 * receives the following parameters:
	 * 
	 * * Join : The new connection. You can use it to retrieve fields and push connections
	 * * Query: The query being joined into. You can use this to retrieve the fields from the uplinks.
	 * 
	 * @param TableObjectInterface $table
	 * @param Closure $fn
	 */
	public function joinTable(TableObjectInterface $table, Closure $fn = null) : Query
	{
		$join = new Join($table);
		$this->joins[] = $join;
		
		$fn !== null && $fn($join, $this);
		
		return $this;
	}
	
	/**
	 * Adds an order clause to the result set, this will be appended and therefore
	 * subordinated to the previously added order clauses.
	 * 
	 * @param OrderBy $order
	 * @return Query
	 */
	public function putOrder (OrderBy $order) 
	{
		$this->order[] = $order;
		return $this;
	}
	
	
	/**
	 * This method returns a finite amount of items matching the parameters from 
	 * the database. This method always returns a collection, even if the result
	 * is empty (no records matched the query)
	 * 
	 * @todo This now feels like it needs to be moved to the model. Where it makes sense to retrieve
	 * the data from the query directly.
	 * 
	 * @param int|null $skip
	 * @param int|null $amt
	 * @return Query
	 */
	public function range(int $skip = null, int $amt = null) : Query
	{
		$this->offset = $skip;
		$this->limit  = $amt;
		return $this;
	}
	
	/**
	 * Returns the number of results that the DBMS should skip before starting to return data.
	 * This is specially useful when creating paginated queries.
	 * 
	 * @return int|null
	 */
	public function getOffset() :? int
	{
		return $this->offset;
	}
	
	/**
	 * Return the maximum number of records the DBMS should be returning when fetching the
	 * result for this query.
	 * 
	 * @return int|null
	 */
	public function getLimit() :? int
	{
		return $this->limit;
	}
	
	/**
	 * Defines a column or array of columns the system will be using to group 
	 * data when generating aggregates.
	 * 
	 * @todo When adding aggregation, the system should automatically use the aggregation for extraction
	 * @todo Currently the system only supports grouping and not aggregation, this is a bit of a strange situation that needs resolution
	 * 
	 * @param QueryField|null $column
	 * @return Query Description
	 */
	public function aggregateBy(QueryField $column = null) 
	{
		if($column === null) { $this->groupBy = []; }
		else                 { $this->groupBy = [$column]; }
		
		return $this;
	}
	
	/**
	 * Add all the fields of a table (implied to be the current query table if null is
	 * passed).
	 * 
	 * @param QueryTable $table
	 * @return Query
	 */
	public function addAllFields(QueryTable $table = null) : Query
	{
		if (!$table) {
			$table = $this->getQueryTable();
		}
		
		$this->calculated = array_merge($this->calculated, $table->getFields());
		return $this;
	}
	
	public function addField(QueryField $field) : Query
	{
		$this->calculated[] = $field;
		return $this;
	}
	
	public function addOutput (OutputObjectInterface $fn) {
		$this->calculated[] = $fn;
		return $this;
	}
	
	
	public function getOrder() {
		return $this->order;
	}
	
	/**
	 * Returns the current 'query table'. This is an object that allows the query
	 * to alias it's table if needed.
	 * 
	 * @return QueryTable
	 */
	public function getQueryTable() {
		return $this->table;
	}
	
	/**
	 * Returns the actual table this query is searching on. 
	 * 
	 * @return QueryTable
	 */
	public function getTable() {
		return $this->table;
	}
	
	public function __toString() {
		return $this->getTable()->getLayout()->getTableName() . implode(',', $this->toArray());
	}
}
