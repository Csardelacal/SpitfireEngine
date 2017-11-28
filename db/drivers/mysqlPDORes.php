<?php namespace spitfire\storage\database\drivers;

use PDO;

/**
 * This class works as a traditional resultset. It acts as an adapter between the
 * driver's raw data retrieving and the logical record classes.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class mysqlPDOResultSet implements \spitfire\storage\database\ResultSetInterface
{
	/**
	 * Contains the raw pointer that PDO has created when executing the query.
	 * This allows spitfire to retrieve all the data needed to create a complete
	 * database record.
	 *
	 * @var PDOStatement
	 */
	private $result;
	
	/**
	 * This is a reference to the table this resultset belongs to. This allows
	 * Spitfire to retrieve data about the model and the fields the datatype has.
	 *
	 * @var \spitfire\storage\database\Table
	 */
	private $table;
	
	public function __construct(\spitfire\storage\database\Table$table, $stt) {
		$this->result = $stt;
		$this->table = $table;
	}

	public function fetch() {
		$data = $this->result->fetch(PDO::FETCH_ASSOC);
		#If the data does not contain anything we return a null object
		if (!$data) { return null; }
		$_record = array_map( Array($this->table->getDB()->getEncoder(), 'decode'), $data);
		
		$record = $this->table->newRecord($_record);
		return $record;
	}

	public function fetchAll() {
		$data = $this->result->fetchAll(PDO::FETCH_ASSOC);
		
		foreach ($data as &$record) {
			$record = $this->table->getDb()->table($this->table->getModel()->getName())->newRecord(
				array_map( Array($this->table->getDB()->getEncoder(), 'decode'), $record)
			);
		}
		
		return $data;
	}
	
	/**
	 * Returns the data the way any associative adapter would return it. This allows
	 * your app to withdraw raw data without it being treated by the framework.
	 * 
	 * @return mixed
	 */
	public function fetchArray() {
		return $this->result->fetch(PDO::FETCH_ASSOC);
	}
	
}
