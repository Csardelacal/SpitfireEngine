<?php namespace spitfire\storage\database;

use Exception;
use spitfire\exceptions\PrivateException;
use spitfire\Model;
use spitfire\model\Field;

abstract class Query extends RestrictionGroup
{
	/** 
	 * The result for the query. Currently, this is attached to the query - this 
	 * means that whenever the query is "re-executed" the result is overwritten 
	 * and could potentially damage the resultset.
	 * 
	 * This would require a significant change in the API, since it requires the
	 * app to not loop fetch() calls over the query but actually retrieve the 
	 * result element and loop over that.
	 * 
	 * @todo This should be removed in favor of an actual collector for the results
	 * @var \spitfire\storage\database\ResultSetInterface|null
	 */
	protected $result;
	
	/** 
	 * The table this query is retrieving data from. This table is wrapped inside
	 * a QueryTable object to ensure that the table can refer back to the query
	 * when needed.
	 * 
	 * @var QueryTable
	 */
	protected $table;
	
	protected $page = 1;
	protected $rpp = -1;
	protected $order;
	protected $groupby = null;
	
	/**
	 *
	 * @deprecated since version 0.1-dev 20160406
	 * @var int|null
	 */
	private $count = null;

	/** @param Table $table */
	public function __construct($table) {
		$this->table = $this->queryTableInstance($table);
		
		#Initialize the parent
		parent::__construct(null, Array());
		$this->setType(RestrictionGroup::TYPE_AND);
	}
	
	/**
	 * 
	 * @param string $fieldname
	 * @param string $value
	 * @param string $operator
	 * @return Query
	 */
	public function addRestriction($fieldname, $value, $operator = '=') {
		$this->result = null;
		return parent::addRestriction($fieldname, $value, $operator);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20160406
	 * @param boolean $aliased
	 */
	public function setAliased($aliased) {
		$this->table->setAliased($aliased);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20160406
	 * @return boolean
	 */
	public function getAliased() {
		return $this->table->isAliased();
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20160406
	 * @return int
	 */
	public function getId() {
		return $this->table->getId();
	}
	
	/**
	 * 
	 * @param int $id
	 * @deprecated since version 0.1-dev 20160406
	 * @return \spitfire\storage\database\Query
	 */
	public function setId($id) {
		$this->table->setId($id);
		return $this;
	}
	
	/**
	 * Since a query is the top Level of any group we can no longer climb up the 
	 * ladder.
	 * 
	 * @throws PrivateException
	 */
	public function endGroup() {
		throw new PrivateException('Called endGroup on a query', 1604031547);
	}
	
	public function getQuery() {
		return $this;
	}

	/**
	 * Sets the amount of results returned by the query.
	 *
	 * @param int $amt
	 *
	 * @return self
	 */
	public function setResultsPerPage($amt) {
		$this->rpp = $amt;
		return $this;
	}
	
	/**
	 * @return int The amount of results the query returns when executed.
	 */
	public function getResultsPerPage() {
		return $this->rpp;
	}
	
	/**
	 * @param int $page The page of results currently displayed.
	 * @return boolean Returns if the page se is valid.
	 */
	public function setPage ($page) {
		#The page can't be lower than 1
		if ($page < 1) return false;
		$this->page = $page;
		return true;
	}
	
	public function getPage() {
		return $this->page;
	}
	
	public function getErrors() {
		return $this->table->getErrors();
	}
	
	//@TODO: Add a decent way to sorting fields that doesn't resort to this awful thing.
	public function setOrder ($field, $mode) {
		try {
			$this->order['field'] = $this->table->getTable()->getField($field);
		} catch (Exception $ex) {
			$physical = $this->table->getTable()->getModel()->getField($field)->getPhysical();
			$this->order['field'] = reset($physical);
		}
		
		$this->order['mode'] = $mode;
		return $this;
	}
	
	/**
	 * Returns a record from a database that matches the query we sent.
	 * 
	 * @return Model
	 */
	public function fetch() {
		if (!$this->result) { $this->query(); }
		
		$data = $this->result->fetch();
		return $data;
	}

	/**
	 * Returns all the records that the query matched. This method wraps the records
	 * inside a collection object to make them easier to access.
	 *
	 * @return \spitfire\core\Collection[]
	 */
	public function fetchAll() {
		if (!$this->result) { $this->query(); }
		return new \spitfire\core\Collection($this->result->fetchAll());
	}

	protected function query($fields = null, $returnresult = false) {
		$result = $this->execute($fields);
		
		if ($returnresult) { return $result; }
		else               { return $this->result = $result; }
	}

	/**
	 * Deletes the records matching this query. This will not retrieve the data and
	 * therefore is more efficient than fetching and later deleting.
	 * 
	 * @todo Currently does not support deleting of complex queries.
	 * @return int Number of affected records
	 */
	public abstract function delete();
	
	/**
	 * Counts the number of records a query would return. If there is a grouping
	 * defined it will count the number of records each group would return.
	 * 
	 * @return type
	 */
	public function count() {
		if (!$this->groupby) {
			//This is a temporary fix that will only count distinct values in complex
			//queries.
			$query = $this->query(Array('COUNT(DISTINCT ' . implode(', ', $this->table->getTable()->getPrimaryKey() ) . ')'), true)->fetchArray();
			$count = reset($query);
			return $this->count = (int)$count;
		}
		elseif(count($this->groupby) === 1) {
			$_ret   = Array();
			$cursor = $this->query(Array(reset($this->groupby), 'count(*)'), true);
			
			while ($row = $cursor->fetchArray()) { $_ret[reset($row)] = end($row); }
			return $_ret;
		}
		
	}
	
	/**
	 * Defines a column or array of columns the system will be using to group 
	 * data when generating aggregates.
	 * 
	 * @param Field|Field[]|null $column
	 * @return Query Description
	 */
	public function aggregateBy($column) {
		if (is_array($column))   { $this->groupby = $column; }
		elseif($column === null) { $this->groupby = null; }
		else                     { $this->groupby = Array($column); }
		
		return $this;
	}
	
	
	/**
	 * Creates the execution plan for this query. This is an array of queries that
	 * aid relational DBMSs' drivers when generating SQL for the database.
	 * 
	 * This basically generate the connecting queries between the tables and injects
	 * your restrictions in between so the system egenrates logical routes that 
	 * will be understood by the relational DB.
	 * 
	 * @return Query[]
	 */
	public function makeExecutionPlan() {
		$_ret = $this->getPhysicalSubqueries();
		array_push($_ret, $this);
		return $_ret;
	}
	
	public function importRestrictions(Query$query) {
		$restrictions = $query->getRestrictions();
		
		foreach($restrictions as $r) {
			$copy = clone $r;
			$copy->setQuery($this);
			$this->putRestriction($copy);
		}
	}
	
	public function getOrder() {
		return $this->order;
	}
	
	public function getQueryTable() {
		return $this->table;
	}
	
	/**
	 * Returns the current 'query table'. This is an object that allows the query
	 * to alias it's table if needed.
	 * 
	 * @return QueryTable
	 */
	public function getTable() {
		return $this->table->getTable();
	}
	
	public function __toString() {
		return $this->getTable() . implode(',', $this->getRestrictions());
	}
	
	public abstract function execute($fields = null);
	public abstract function restrictionInstance(QueryField$field, $value, $operator);
	public abstract function compositeRestrictionInstance(Field$field = null, $value, $operator);
	
	/**
	 * Creates a new instance of a restriction group for this query. The instance
	 * is already created with a reference to this element. This is just used in 
	 * a set of cases, when creating a restriction (so it keeps the reference to
	 * the query) and when "ending the group" which basically returns the call flow
	 * over to the query.
	 * 
	 * @return \spitfire\storage\database\RestrictionGroup
	 */
	public abstract function restrictionGroupInstance($parent);
	public abstract function queryFieldInstance($field);
	public abstract function queryTableInstance($table);
}
