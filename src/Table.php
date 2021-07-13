<?php namespace spitfire\storage\database;

use Model;
use spitfire\exceptions\PrivateException;

/**
 * This class simulates a table belonging to a database. This way we can query
 * and handle tables with 'compiler-friendly' code that will inform about errors.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class Table
{

	/**
	 * A reference to the database driver loaded. This allows the system to 
	 * use several databases without the models colliding.
	 *
	 * @var DB
	 */
	protected $db;
	
	/**
	 * Provides access to the table's layout (physical schema) 
	 * 
	 * @var LayoutInterface
	 */
	private $layout = false;
	
	/**
	 * Provides access to the table's record operations. Basically, a relational
	 * table is composed of schema + relation (data).
	 *
	 * @var Relation
	 */
	private $relation;
	
	/**
	 * Caches a list of fields that compound this table's primary key. The property
	 * is empty when the table is constructed and collects the primary key's fields
	 * once they are requested for the first time.
	 * 
	 * @var \spitfire\storage\database\Index|null
	 */
	protected $primaryK;
	
	/**
	 * Just like the primary key field, this property caches the field that contains
	 * the autonumeric field. This will usually be the ID that the DB refers to 
	 * when working with the table.
	 *
	 * @var Field
	 */
	protected $autoIncrement;

	/**
	 * Creates a new Database Table instance. The tablename will be used to find
	 * the right model for the table and will be stored prefixed to this object.
	 *
	 * @param DB     $db
	 * @param Layout $layout
	 *
	 * @throws PrivateException
	 */
	public function __construct(DB $db, LayoutInterface $layout) 
	{
		/**
		 * The table will always exist within a defined scope, the scope is provided by the database
		 * driver object.
		 */
		$this->db = $db;
		
		#Create a database table layout (physical schema)
		$this->layout = $layout;
		
		/**
		 * The relation represents the data within the table. It's the object giving you access
		 * to CRUD operations and querying.
		 */
		$this->relation = new Relation($this);
	}
	
	/**
	 * Returns the database the table belongs to.
	 * @return DB
	 */
	public function getDb() {
		return $this->db;
	}
	
	/**
	 * Get's the table's primary key. This will always return an array
	 * containing the fields the Primary Key contains.
	 * 
	 * @return IndexInterface
	 */
	public function getPrimaryKey() {
		/*
		 * If the primary was already determined, we use the cached version.
		 */
		if ($this->primaryK) { return $this->primaryK; }
		
		$indexes = $this->layout->getIndexes();
		$this->primaryK = $indexes->filter(function (IndexInterface$i) { return $i->isPrimary(); })->rewind();
		
		/**
		 * This safeguard enforces a new rule that should make Spitfire incredibly 
		 * more easy to work with. Primary keys (and therefore references, children, etc)
		 * are no longer allowed to have more than one field. If the user disregards
		 * this, the application will fail here.
		 * 
		 * Please note that you can disable this check in production by disabling assertions,
		 * this is for optimzation purposes only, and the application will not behave
		 * properly if the check is disabled to circumvent the error.
		 */
		assert(!$this->primaryK || $this->primaryK->count() === 1);
		
		return $this->primaryK;
	}
	
	public function getAutoIncrement() {
		if ($this->autoIncrement) { return $this->autoIncrement; }
		
		//Implicit else
		$fields  = $this->layout->getFields();
		
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
	 * This function is intended to be used to provide controllers with prebuilt
	 * models so they don't need to fetch it again.
	 *
	 * @todo Move to relation
	 *
	 * @param mixed $id
	 *
	 * @return Model
	 */
	public function getById($id) {
		#If the data is a string separate by colons
		if (!is_array($id)) { $id = explode(':', $id); }
		
		#Create a query
		$table   = $this;
		$primary = $table->getPrimaryKey()->getFields();
		$query   = $table->getDb()->getObjectFactory()->queryInstance($this);
		
		#Add the restrictions
		while(!$primary->isEmpty()) { 
			$query->where($primary->shift(), array_shift($id));
		}
		
		#Return the result
		$_return = $query->fetch();
		
		return $_return;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20160902
	 * @return Layout
	 */
	public function getModel() {
		return $this->layout;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20170801
	 * @return Relation
	 */
	public function getCollection() {
		return $this->relation;
	}
	
	/**
	 * Gives access to the relation, the table's component that manages the data
	 * that the table contains.
	 * 
	 * @return Relation
	 */
	public function getRelation() {
		return $this->relation;
	}
	
	/**
	 * 
	 * @return LayoutInterface
	 */
	public function getLayout(): LayoutInterface {
		return $this->layout;
	}
	
	/**
	 * 
	 * @deprecated since 0.2
	 * @return Layout
	 */
	public function getSchema() {
		return $this->layout;
	}
	
	public function get($field, $value, $operator = '=') {
		return $this->relation->get($field, $value, $operator);
	}
	
	public function getAll() {
		return $this->relation->getAll();
	}
	
	public function newRecord($data = Array()) {
		return $this->relation->newRecord($data);
	}

}
