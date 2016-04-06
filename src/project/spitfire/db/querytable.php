<?php

namespace spitfire\storage\database;

abstract class QueryTable
{
	private $table;
	
	/**
	 * The query is no longer needed for the aliasing, since the table now takes
	 * care of it autonomously.
	 * 
	 * @deprecated since version 0.1-dev 20160406
	 * @var Query
	 */
	private $query;
	
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
	private $id;
	private $aliased = false;
	
	public function __construct(Query$query, Table$table) {
		#In case this table is aliased, the unique alias will be generated using this.
		$this->id = self::$counter++;
		
		$this->query = $query;
		$this->table = $table;
	}
	
	/**
	 * 
	 * @return \spitfire\storage\database\Query
	 */
	public function getQuery() {
		return $this->query;
	}
	
	public function setQuery($query) {
		$this->query = $query;
	}
	
	public function getId() {
		return $this->id;
	}
	
	public function setId($id) {
		$this->id = $id;
	}
	
	public function setAliased($aliased) {
		$this->aliased = $aliased;
	}
	
	public function isAliased() {
		return $this->aliased;
	}
	
	public function getAlias() {
		return $this->aliased? 
				sprintf('%s_%s', $this->table->getTablename(), $this->id) : 
				$this->table->getTablename();
	}
	
	/**
	 * 
	 * @return \spitfire\storage\database\Table
	 */
	public function getTable() {
		return $this->table;
	}
	
	abstract public function definition();
	abstract public function __toString();
}