<?php

namespace spitfire\storage\database\drivers;

use PDO;
use Reference;
use ChildrenField;
use Model;

/**
 * This class works as a traditional resultset. It acts as an adapter between the
 * driver's raw data retrieving and the logical record classes.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class mysqlPDOResultSet implements resultSetInterface
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
	 * @var spitfire\storage\database\Table 
	 */
	private $table;
	
	public function __construct(MysqlPDOTable$table, $stt) {
		$this->result = $stt;
		$this->table = $table;
	}

	public function fetch() {
		$data = $this->result->fetch(PDO::FETCH_ASSOC);
		#If the data does not contain anything we return a null object
		if (!$data) return null;
		$data = array_map( Array($this->table->getDB(), 'convertIn'), $data);
		
		#Once the data is clean parse it in
		$_record = Array();
		$fields  = $this->table->getModel()->getFields();
		
		foreach ($fields as $field) {
			
			if ($field instanceof Reference) {
				$physical = $field->getPhysical();
				
				#If the primary key of the parent only has 1 field we pass it through
				#a cachable query via getbyid
				if (count($physical) == 1) {
					$query = $this->table->getDb()->table($field->getTarget())->getById($data[reset($physical)->getName()]);
				}
				else {
					$query    = $this->table->getDb()->table($field->getTarget())->getAll();

					foreach ($physical as $physical_field) {
						$query->addRestriction($physical_field->getReferencedField()->getName(), $data[$physical_field->getName()]);
					}
				}
				
				$_record[$field->getName()] = $query;
			}
			
			elseif ($field instanceof \MultiReference) {
				
			}
			
			elseif ($field instanceof ChildrenField) {
				if ($field->getTarget()->getField($field->getRole()) instanceof Reference) {
					$query = $field->getTarget()->getTable()->getAll();
					$remote = $field->getTarget()->getField($field->getRole())->getPhysical();
					foreach ($remote as $f) {
						$name = $f->getName();
						$value = $data[$f->getReferencedField()->getName()];
						$query->addRestriction($name, $value);
					}
					$_record[$field->getName()] = $query;
				}
			}
			
			else {
				$_record[$field->getName()] = $data[array_shift($field->getPhysical())->getName()];
			}
			
		}
		
		$record = $this->table->newRecord($_record);
		return $record;
	}

	public function fetchAll(Model$parent = null) {
		//TODO: Swap to fatch all
		//$data = $this->result->fetchAll(PDO::FETCH_ASSOC);
		$_return = Array();
		$fields  = $this->table->getModel()->getFields();
		$parentConnector = Array();
		
		if ($parent)
		foreach ($fields as $field) {
			if ($field instanceof Reference && ($field->getTarget()) === $parent->getTable()->getModel())
				$parentConnector[] = $field;
		}
		
		while ($data = $this->fetch()) {
			foreach ($parentConnector as $field) {
				$data->{$field->getName()} = $parent;
			}
			
			$_return[] = $data;
		}
		return $_return;
	}
	

	public function fetchArray() {
		return $this->result->fetch(PDO::FETCH_ASSOC);
	}
	
	public function __destruct() {
		$this->result->closeCursor();
	}
}