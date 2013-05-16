<?php

namespace spitfire\storage\database\drivers;

use PDO;

class mysqlPDOResultSet implements resultSetInterface
{
	private $result;
	private $table;
	
	public function __construct($table, $stt) {
		$this->result = $stt;
		$this->table = $table;
	}

	public function fetch() {
		$data = $this->result->fetch(PDO::FETCH_ASSOC);
		#If the data does not contain anything we return a null object
		if (!$data) return null;
		$data = array_map( Array($this->table->getDB(), 'convertIn'), $data);
		return $this->table->newRecord($data);
	}

	public function fetchAll() {
		$data = $this->result->fetchAll(PDO::FETCH_ASSOC);
		foreach($data as &$el) $el = $this->table->newRecord(array_map( Array($this->table->getDB(), 'convertIn'), $el));
		return $data;
	}
	

	public function fetchArray() {
		return $this->result->fetch(PDO::FETCH_ASSOC);
	}
	
	public function __destruct() {
		$this->result->closeCursor();
	}
}