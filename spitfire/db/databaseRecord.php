<?php

use spitfire\storage\database\Table;
use spitfire\storage\database\DBField;
use spitfire\storage\database\Query;

/**
 * This class allows to track changes on database data along the use of a program
 * and creates interactions with the database in a safe way.
 * 
 * @todo Make this class implement Iterator
 * @author César de la Cal <cesar@magic3w.com>
 */
class Model implements Serializable
{
	
	/**
	 * The actual data that the record contains. The record is basically a wrapper
	 * around the array that allows to validate data on the go and to alert the 
	 * programmer about inconsistent types.
	 * 
	 * @var spitfire\model\adapters\AdapterInterface[] 
	 */
	private $data;
	
	/**
	 * Keeps information about the table that owns the record this Model represents.
	 * This allows it to power functions like store that require knowledge about 
	 * the database keeping the information.
	 * 
	 * @var Table
	 */
	private $table;
	
	#Status vars
	private $new = false;
	private $deleted = false;
	
	/**
	 * Creates a new record.
	 * 
	 * @param Table $table DB Table this record belongs to. Easiest way
	 *                       to get this is by using $this->model->*tablename*
	 * 
	 * @param mixed $srcData Attention! This parameter is intended to be 
	 *                       used by the system. To create a new record, leave
	 *                       empty and use setData.
	 */
	public function __construct(Table $table, $data) {
		
		$this->table   = $table;
		$this->new     = empty($data);
		
		$this->makeAdapters();
		$this->populateAdapters($data);
	}
	
	/**
	 * This method is used to generate the 'template' for the table that allows
	 * spitfire to automatically generate tables and allows it to check the types
	 * of data and fix tables.
	 * 
	 * @abstract
	 */
	//public static abstract function definitions();
	
	/**
	 * Receives the data of an array and stores it into this record. This
	 * does not verify the data is correct nor does it check if the data
	 * fits into the table. You're assumed to pass the data correctly.
	 * 
	 * @param mixed $newdata
	 * @deprecated since version 0.1-dev
	 */
	public function setData($newdata) {
		$this->data = $newdata;
	}
	
	/**
	 * Returns the data this record currently contains as associative array.
	 * Remember that this data COULD be invalid when using setData to provide
	 * it.
	 * 
	 * @return mixed
	 */
	public function getData() {
		return $this->data;
	}
	
	/**
	 * Data contained by the database. Note that it is possible that this
	 * function does provide data not actually in the DB (the record can have
	 * been destroyed or modified, or this data altered)
	 * 
	 * @return mixed
	 * @deprecated since version 0.1-dev
	 */
	public function getSrcData() {
		return $this->src;
	}
	
	/**
	 * This function checks whether the data contained in this record is
	 * 'in sync' with the DB. Being in sync means that the data contained
	 * by this record is supposed to be the same as the physical record
	 * on the DBMS.
	 * 
	 * @return boolean True if the data is in sync with the DB
	 */
	public function isSynced() {
		return $this->synced && !$this->deleted;
	}
	
	/**
	 * This method stores the data of this record to the database. In case
	 * of database error it throws an Exception and leaves the state of the
	 * record unchanged.
	 * 
	 * @throws privateException
	 */
	public function store() {
		
		if (is_callable(Array($this, 'onbeforesave'))) {
			$this->onbeforesave();
		}
		
		if ($this->new) {
			$id = $this->insert();
			$ai = $this->table->getAutoIncrement();
			
			if ($ai && empty($this->data[$ai->getName()]) ) {
				$this->data[$ai->getName()]->dbSetData($id);
			}
		}
		else {
			$this->update($this);
		}
		
		$this->new    = false;
		
		foreach($this->data as $field => $value) {
			$value->commit();
		}
	}


	/**
	 * Returns the fields that compound the primary key of this record.
	 * 
	 * @return DBField[]|spitfire\storage\database\DBField[]
	 */
	public function getUniqueFields() {
		return $this->table->getPrimaryKey();
	}
        
	/**
	 * Returns the values of the fields included in this records primary
	 * fields
	 * 
	 * @todo Find better function name
	 * @return Array
	 */
	public function getPrimaryData() {
		$primaryFields = $this->getUniqueFields();
		$ret = Array();
	    
		foreach ($primaryFields as $field) {
			$logical = $field->getLogicalField();
			$ret = array_merge($ret, $this->data[$logical->getName()]->dbGetData());
	    }
	    
	    return $ret;
	}
	
	/**
	 * Creates a list of restrictions that identify this record inside it's
	 * database table.
	 * 
	 * @todo Fix, still works the old way.
	 * @deprecated since version 0.1
	 * @return Restriction[]
	 */
	public function getUniqueRestrictions() {
		$primaries    = $this->table->getPrimaryKey();
		$restrictions = Array();
		$query        = $this->table->getQueryInstance();
		
		foreach($primaries as $primary) {
			$ref   = $primary->getReferencedField();
			$value = &$this->src[$primary->getLogicalField()->getName()];
			
			if ($value instanceof Query) $value = $value->fetch();
			if ($value instanceof Model) {
				$value = $value->{$ref->getName()};
			}
			
			$r = $query->restrictionInstance($query->queryFieldInstance($primary), $value, '=');
			$restrictions[] = $r;
			
			unset($value);
		}
		
		return $restrictions;
	}
	
	public function getQuery() {
		$query     = $this->getTable()->queryInstance($this->getTable());
		$primaries = $this->table->getModel()->getPrimary();
		
		foreach ($primaries as $primary) {
			$name = $primary->getName();
			$query->addRestriction($name, $this->$name);
		}
		
		return $query;
	}
	
	/**
	 * Returns the table this record belongs to.
	 * 
	 * @return spitfire\storage\database\Table
	 */
	public function getTable() {
		return $this->table;
	}

	public function __set($field, $value) {
		
		if (!isset($this->data[$field])) {
			throw new privateException("Setting non existent field: " . $field);
		}
		
		$this->data[$field]->usrSetData($value);
	}
	
	public function __get($field) {
		#If the field is in the record we return it's contents
		if (isset($this->data[$field])) {
			return $this->data[$field]->usrGetData();
		} else {
			//TODO: In case debug is enabled this should throw an exception
			return null;
		}
	}
	
	public function serialize() {
		if (! $this->synced) throw new privateException("Database record cannot be serialized out of sync");
		
		$output = Array();
		$output['model'] = $this->table->getModel()->getName();
		$output['data']  = $this->data;
		
		return serialize($output);
	}
	
	public function unserialize($serialized) {
		
		$input = unserialize($serialized);
		$this->table = db()->table($input['model']);
		$this->data  = $input['data'];
	}
	
	public function __toString() {
		return sprintf('%s(%s)', $this->getTable()->getModel()->getName(), implode(',', $this->getPrimaryData()) );
	}
	
	public function delete() {
		$this->table->delete($this);
	}
	
	public function insert() {
		return $this->table->insert($this);
	}
	
	public function update() {
		$this->table->update($this);
	}
	
	public function restrictionInstance($query, DBField$field, $value, $operator = null) {
		return $this->table->restrictionInstance($query, $field, $value, $operator);
	}
	
	/**
	 * Increments a value on high read/write environments. Using update can
	 * cause data to be corrupted. Increment requires the data to be in sync
	 * aka. stored to database.
	 * 
	 * @param String $key
	 * @param int|float $diff
	 * @throws privateException
	 */
	public function increment($key, $diff = 1) {
		$this->table->increment($this, $key, $diff);
	}
	
	protected function makeAdapters() {
		$fields = $this->getTable()->getModel()->getFields();
		foreach ($fields as $field) {
			$this->data[$field->getName()] = $field->getAdapter($this);
		}
	}
	
	protected function populateAdapters($data) {
		$fields = $this->getTable()->getModel()->getFields();
		foreach ($fields as $field) {
			$physical = $field->getPhysical();
			$current  = Array();
			foreach ($physical as $p) {
				$current[$p->getName()] = $data[$p->getName()];
			}
			$this->data[$field->getName()]->dbSetData($current);
		}
	}

}
