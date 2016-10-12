<?php namespace spitfire\storage\database;

use CoffeeBean;
use spitfire\core\Environment;
use spitfire\exceptions\PrivateException;
use spitfire\storage\database\Schema;

/**
 * This class simulates a table belonging to a database. This way we can query
 * and handle tables with 'compiler-friendly' code that will inform about errors.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
abstract class Table
{

	/**
	 * A reference to the database driver loaded. This allows the system to 
	 * use several databases without the models colliding.
	 *
	 * @var DB
	 */
	protected $db;
	
	/**
	 * The model this table uses as template to create itself on the DBMS. This is
	 * one of the key components to Spitfire's ORM as it allows the DB engine to 
	 * create the tables automatically and to discover the data relations.
	 *
	 * @var Schema 
	 */
	protected $model;
	
	/**
	 * The prefixed name of the table. The prefix is defined by the environment
	 * and allows to have several environments on the same database.
	 *
	 * @var string
	 */
	protected $tablename;
	
	/**
	 * List of the physical fields this table handles. This array is just a 
	 * shortcut to avoid looping through model-fields everytime a query is
	 * performed.
	 *
	 * @var DBField[] 
	 */
	protected $fields;
	
	/**
	 * Contains the bean this table uses to generate forms for itself. The bean
	 * contains additional data to make the data request more user friendly.
	 * 
	 * @var CoffeeBean
	 */
	protected $bean;
	
	/**
	 * Caches a list of fields that compound this table's primary key. The property
	 * is empty when the table is constructed and collects the primary key's fields
	 * once they are requested for the first time.
	 * 
	 * @var DBField[]|null
	 */
	protected $primaryK;
	
	/**
	 * Just like the primary key field, this property caches the field that contains
	 * the autonumeric field. This will usually be the ID that the DB refers to 
	 * when working with the table.
	 *
	 * @var DBField
	 */
	protected $autoIncrement;

	/**
	 * Creates a new Database Table instance. The tablename will be used to find 
	 * the right model for the table and will be stored prefixed to this object.
	 * 
	 * @param DB $db
	 * @param string|Schema $schema
	 */
	public function __construct(DB$db, $schema) {
		$this->db = $db;
		
		if ($schema instanceof Schema) {
			$this->model = $schema;
			$this->model->setTable($this);
		} else {
			throw new PrivateException('Table requires a Schema to be passed');
		}
		
		#Get the physical table name. This will use the prefix to allow multiple instances of the DB
		$this->tablename = Environment::get('db_table_prefix') . $this->model->getTableName();
		
		$this->makeFields();
	}
	
	public function makeFields() {
		
		$fields   = $this->model->getFields();
		$dbfields = Array();
		
		foreach ($fields as $field) {
			$physical = $field->getPhysical();
			while ($phys = array_shift($physical)) { $dbfields[$phys->getName()] = $phys; }
		}
		
		$this->fields = $dbfields;
	}
	
	/**
	 * Fetch the fields of the table the database works with. If the programmer
	 * has defined a custom set of fields to work with, this function will
	 * return the overriden fields.
	 * 
	 * @return DBField[] The fields this table handles.
	 */
	public function getFields() {
		return $this->fields;
	}
	
	public function getField($name) {
		#If the data we get is already a DBField check it belongs to this table
		if ($name instanceof DBField) {
			if ($name->getTable() === $this) { return $name; }
			else { throw new PrivateException('Field ' . $name . ' does not belong to ' . $this); }
		}
		
		#Otherwise search for it in the fields list
		if (isset($this->fields[(string)$name])) { return $this->fields[(string)$name]; }
		
		#The field could not be found in the Database
		throw new PrivateException('Field ' . $name . ' does not exist in ' . $this);
	}
	
	/**
	 * Returns the name of the table that is being used. The table name
	 * includes the database's prefix.
	 *
	 * @return string 
	 */
	public function getTablename() {
		return $this->tablename;
	}
	
	/**
	 * Returns the database the table belongs to.
	 * @return DB|DB
	 */
	public function getDb() {
		return $this->db;
	}
	
	/**
	 * Get's the table's primary key. This will always return an array
	 * containing the fields the Primary Key contains.
	 * 
	 * @return Array Name of the primary key's column
	 */
	public function getPrimaryKey() {
		//Check if we already did this
		if ($this->primaryK !== null) { return $this->primaryK; }
		
		//Implicit else
		$fields  = $this->getFields();
		$pk      = Array();
		
		foreach($fields as $name => $field) {
			if ($field->getLogicalField()->isPrimary()) { $pk[$name] = $field; }
		}
		
		return $this->primaryK = (array) $pk;
	}
	
	public function getAutoIncrement() {
		if ($this->autoIncrement) { return $this->autoIncrement; }
		
		//Implicit else
		$fields  = $this->getFields();
		
		foreach($fields as $field) {
			if ($field->getLogicalField()->isAutoIncrement()) { return  $this->autoIncrement = $field; }
		}
		
		 return null;
	}
	
	/**
	 * Looks for a record based on it's primary data. This can be one of the
	 * following:
	 * <ul>
	 * <li>A single basic data field like a string or a int</li>
	 * <li>A string separated by : to separate those fields (SF POST standard)</li>
	 * <li>An array with the data</li>
	 * </ul>
	 * 
	 * This function is intended to be used to provide controllers with prebuilt
	 * models so they don't need to fetch it again.
	 * 
	 * @todo Move to collection
	 * @param mixed $id
	 */
	public function getById($id) {
		#If the data is a string separate by colons
		if (!is_array($id)) { $id = explode(':', $id); }
		
		#Create a query
		$table   = $this;
		$primary = $table->getPrimaryKey();
		$query   = $table->getDb()->getObjectFactory()->getQueryInstance();
		
		#Add the restrictions
		while(count($primary))
			{ $query->addRestriction (array_shift($primary), array_shift($id)); }
		
		#Return the result
		$_return = $query->fetch();
		
		return $_return;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20160902
	 * @return \Schema
	 */
	public function getModel() {
		return $this->model;
	}
	
	public function getCollection() {
		return $this->db->table($this->model->getName());
	}
	
	/**
	 * 
	 * @return \Schema
	 */
	public function getSchema() {
		return $this->model;
	}
	
	/**
	 * Returns the bean this model uses to generate Forms to feed itself with data
	 * the returned value normally is a class that inherits from CoffeeBean.
	 * 
	 * @return CoffeeBean
	 */
	public function getBean($name = null) {
		
		if (!$name) { $beanName = $this->model->getName() . 'Bean'; }
		else        { $beanName = $name . 'Bean'; }
		
		$bean = new $beanName($this);
		
		return $bean;
	}
	
	/**
	 * Creates a table on the DBMS that is capable of holding the Model's data 
	 * appropriately. This will try to normalize the data as far as possible to 
	 * create consistent databases.
	 * 
	 * @todo Move to Table
	 */
	abstract public function create();
	abstract public function repair();
	public abstract function destroy();
	
	/**
	 * If the table cannot handle the request it will pass it on to the db
	 * and add itself to the arguments list.
	 * 
	 * @param string $name
	 * @param mixed $arguments
	 */
	public function __call($name, $arguments) {
		#Add the table to the arguments for the db
		array_unshift($arguments, $this);
		#Pass on
		return call_user_func_array(Array($this->db, $name), $arguments);
	}

}