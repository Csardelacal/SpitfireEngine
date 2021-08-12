<?php namespace spitfire\storage\database;

use spitfire\collection\Collection;

/**
 * The query table wraps a table and provides a consistent aliasing mechanism.
 * This allows the system to reference tables within the database system across
 * queries.
 * 
 * For example, when performing a query that requires a table to be joined twice,
 * the application needs to consistently alias the fields in the query. In SQL
 * we usually write something like
 * 
 * SELECT * FROM orders LEFT JOIN customers c1 ON (...) LEFT JOIN customers c2 ON (...)
 * 
 * And then reference the fields within them as c1.id or c2.id. Otherwise, the DBMS
 * will fail, indicating that the field `id` is ambiguous.
 */
class QueryTable
{
	
	/**
	 * This table provides all the information (metadata and fields) about the table
	 * being queried.
	 * 
	 * @var Table
	 */
	private $table;
	
	/**
	 * The following variables manage the aliasing system inside spitfire. To avoid
	 * having different tables with the same name in them, Spitfire uses aliases
	 * for the tables. These aliases are automatically generated by adding a unique
	 * number to the table's name.
	 * 
	 * The counter is in charge of making sure that every table is uniquely named,
	 * every time a new query table is created the current value is assigned and
	 * incremented.
	 *
	 * @var int
	 */
	private static $counter = 1;
	
	/**
	 * The id is used internally, and is assigned by incrementing the static counter. This
	 * means that every query table will have a unique id that makes it consistent when disambiguating
	 * tables.
	 * 
	 * @var int
	 */
	private $id;
	
	/**
	 * The aliased flag indicates whether the driver should alias this table. This makes
	 * the generated SQL more readable when debugging.
	 * 
	 * @var bool
	 */
	private $aliased = false;
	
	/**
	 * @param Table $table
	 */
	public function __construct(Table $table) 
	{
		#In case this table is aliased, the unique alias will be generated using this.
		$this->id = self::$counter++;
		$this->table = $table;
	}
	
	/**
	 * Get the ID for this table. This is used to generate the table's alias.
	 * 
	 * @return int
	 */
	public function getId() : int
	{
		return $this->id;
	}
	
	/**
	 * Creates a copy of this query table, generating a new ID in the process. This is
	 * due to the fact that these aliases are intended to be immutable.
	 * 
	 * @return QueryTable
	 */
	public function withNewId() : QueryTable
	{
		return new QueryTable($this->table);
	}
	
	/**
	 * Determines whether this query table is handled as aliased.
	 * 
	 * @param bool $aliased
	 * @return QueryTable
	 */
	public function setAliased(bool $aliased) : QueryTable
	{
		$this->aliased = $aliased;
		return $this;
	}
	
	/**
	 * Indicates whether this table has been aliased. If this is the case, most DBMS
	 * will need to change the definition of the table when querying.
	 * 
	 * @return bool
	 */
	public function isAliased() : bool
	{
		return $this->aliased;
	}
	
	/**
	 * Retrieves the table's alias. Please note that if the table is set to not alias,
	 * the system will return the table name. This quirk makes the method rather convenient
	 * to use.
	 * 
	 * @return string
	 */
	public function getAlias() : string
	{
		/*
		 * Get the name for the table. We use it to provide a consistent naming
		 * system that makes it easier for debugging.
		 */
		$name = $this->table->getLayout()->getTablename();
		
		return $this->aliased? sprintf('%s_%s', $name, $this->id) : $name;
	}
	
	/**
	 * Gets a queryfield.
	 * 
	 * @param string $name
	 * @return QueryField
	 */
	public function getField(string $name) : QueryField
	{
		return new QueryField($this, $this->table->getLayout()->getField($name));
	}
	
	/**
	 * 
	 * @return Collection
	 */
	public function getFields() : Collection
	{
		$fields = $this->table->getLayout()->getFields();
		
		return $fields->each(function ($e) {
			return new QueryField($this, $e);
		});
	}
	
	/**
	 * 
	 * @return Table
	 */
	public function getTable() {
		return $this->table;
	}
	
}
