<?php namespace spitfire\storage\database;

use BadMethodCallException;
use Closure;
use spitfire\collection\Collection;
use spitfire\storage\database\query\Alias;
use spitfire\storage\database\query\Join;
use spitfire\storage\database\query\JoinTable;
use spitfire\storage\database\query\Restriction;
use spitfire\storage\database\query\RestrictionGroup;

/**
 * The query provides a mechanism for assembling restrictions that Spitfire and
 * the DBMS driver can then convert into a SQL query (or similar, for NoSQL).
 * 
 * @todo The properties are protected, when they should actually be private
 * @mixin RestrictionGroup
 */
class Query
{
	
	/** 
	 * The table this query is retrieving data from. This table is wrapped inside
	 * a QueryTable object to ensure that the table can refer back to the query
	 * when needed.
	 * 
	 * @var Alias
	 */
	protected $from;
	
	/**
	 *
	 * @var Collection<OrderBy>
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
	 * @todo Introduce utility methods for the joins
	 * @var Collection<Join>
	 */
	private $joins;
	
	/**
	 * 
	 * @var RestrictionGroup
	 */
	private $restrictions;
	
	/**
	 * This contains an array of aggregation functions that are executed with the 
	 * query to provide metadata on the query.
	 * 
	 * @todo Provide a single output kind of type (something that can either refer to a field or a aggreation)
	 * @todo Rename to reflect the fact that this is what e expect as output of the query.
	 * @var Collection<FieldReference|Aggregate>
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
	 * @var Collection<FieldReference>
	 */
	protected $groupBy;
	
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
	 * @param TableReference $table 
	 */
	public function __construct(TableReference $table) 
	{
		$this->from = new Alias($table, $table->withAlias());
		$this->joins = new Collection();
		$this->restrictions = new RestrictionGroup();
		$this->calculated = new Collection();
		$this->groupBy = new Collection();
		$this->order = new Collection();
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
	 * @param TableReference $table
	 * @param Closure $fn
	 */
	public function joinTable(TableReference $table, Closure $fn = null) : Query
	{
		$join = new JoinTable(new Alias($table, $table->withAlias()));
		$this->joins[] = $join;
		
		$fn !== null && $fn($join, $this);
		
		return $this;
	}
	
	/**
	 * 
	 * @return Collection<Join>
	 */
	public function getJoined() : Collection
	{
		return $this->joins;
	}
	
	public function getRestrictions() : RestrictionGroup
	{
		return $this->restrictions;
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
	 * @param FieldReference[] $columns
	 * @return Query Description
	 */
	public function groupBy(array $columns = []) 
	{
		$this->groupBy = $columns;
		return $this;
	}
	
	/**
	 * Returns the fields this query is grouped by.
	 * 
	 * @return Collection<FieldReference>
	 */
	public function getGroupBy() : Collection
	{
		return $this->groupBy;
	}
	
	/**
	 * Add all the fields of a table (implied to be the current query table if null is
	 * passed).
	 * 
	 * @param TableReference $table
	 * @return Query
	 */
	public function selectAll(TableReference $table = null) : Query
	{
		$_t = $table !== null? $table : $this->from->output();
		$this->calculated->add($_t->getOutputs());
		return $this;
	}
	
	public function select(string $name, TableReference $table = null) : Query
	{
		$field = ($table?: $this->from->output())->getOutput($name);
		$this->calculated->push($field);
		return $this;
	}
	
	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @param Closure $generator
	 * @return RestrictionGroup
	 */
	public function whereExists(Closure $generator) : Query
	{
		$value = $generator($this);
		assert($value instanceof Query);
		
		$this->restrictions->push(new Restriction(null, Restriction::EQUAL_OPERATOR, $value));
		return $this;
	}
	
	public function aggregate (Aggregate $fn) : Query
	{
		$this->calculated->push($fn);
		return $this;
	}
	
	public function getOutputs() : Collection
	{
		return (new Collection($this->calculated))->each(function ($e) {
			/*
			 * We only accept aggregates and field references here. Everything else should
			 * have been converted
			 */
			assert($e instanceof Aggregate || $e instanceof FieldReference);
			
			if ($e instanceof Aggregate) { return $e->getOutput(); }
			if ($e instanceof FieldReference) { return $e->getName(); }
		});
	}
	
	public function getOutputsRaw() : Collection
	{
		return $this->calculated;
	}
	
	/**
	 * 
	 * @return Collection<OrderBy>
	 */
	public function getOrder() : Collection
	{
		return $this->order;
	}
	
	/**
	 * 
	 * @return Alias
	 */
	public function getFrom() : Alias
	{
		return $this->from;
	}
	
	/**
	 * Returns the actual table this query is searching on. 
	 * 
	 * @return TableReference
	 */
	public function getTable() {
		return $this->from->output();
	}
	
	/**
	 * 
	 * @param string $name
	 * @param mixed $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		if (method_exists($this->restrictions, $name)) {
			return $this->restrictions->$name(...$arguments);
		}
		
		throw new BadMethodCallException(sprintf('Undefined method Query::%s', $name));
	}
	
	public function __toString() {
		return sprintf(
			'%s(%s) {%s}',
			$this->from->input()->getName(),
			$this->from->output()->getName(),
			implode(',', $this->restrictions->toArray())
		);
	}
}
