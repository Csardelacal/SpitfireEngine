<?php

namespace spitfire\storage\database;

/**
 * Represents a table's field in a database. Contains information about the
 * table the field belongs to, the name of the field and if it is (or not) a
 * primary key or auto-increment field.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class Field
{
	private $table;
	private $name;
	private $primary;
	private $auto_increment;
	
	public function __construct(Table$table, $name, $primary = false, $auto_increment = false) {
		
		$this->table          = $table;
		$this->name           = $name;
		$this->primary        = !!$primary;
		$this->auto_increment = !!$auto_increment;
		
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getTable() {
		return $this->table;
	}
	
	public function isAutoIncrement() {
		return $this->auto_increment;
	}
	
	public function isPrimary() {
		return $this->primary;
	}
	
	/**
	 * Tries to call the Driver's stringifyField method. If this method isn't
	 * available it will default to \`table\`.\`field\` which is a widespread
	 * form of referencing fields in DBMS's.
	 * 
	 * @return string Name of the field object the database can use to query
	 *                data.
	 */
	public function __toString() {
		
		$con       = $this->table->getDb()->getConnection();
		$stringify = Array($con, 'stringifyField');
		
		if (method_exists($stringify[0], $stringify[1])) {
			$data = Array($this->table, $this->name);
			return call_user_func_array ($stringify, $data);
		}
		else {
			return "`{$this->table->getTablename()}`.`{$this->name}`";
		}
	}
}