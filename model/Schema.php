<?php namespace spitfire\model;

use EnumField;
use FloatField;
use ManyToManyField;
use spitfire\collection\Collection;
use spitfire\model\fields\IntegerField;
use spitfire\exceptions\PrivateException;
use spitfire\model\Field;
use spitfire\model\fields\ChildrenField;
use spitfire\model\fields\Reference;
use spitfire\model\fields\StringField;
use spitfire\model\Index;
use spitfire\storage\database\Query;
use spitfire\storage\database\Table;
use TextField;

/**
 * A Schema is a class used to define how Spitfire stores data into a DBMS. We
 * usually consider a DBMS as relational database engine, but Spitfire can
 * be connected to virtually any engine that stores data. Including No-SQL
 * databases and directly on the file system. You should even be able to use
 * tapes, although that would be extra slow.
 * 
 * Every model contains fields and references. Fields are direct data-types, they
 * allow storing things directly into them, while references are pointers to 
 * other models allowing you to store more complex data into them.
 * 
 * @property IntegerField $_id This default primary key integer helps the system 
 * locating the records easily.
 * 
 * @todo Add index support, so models can create indexes that are somewhat more complex
 * @author César de la Cal <cesar@magic3w.com>
 */
class Schema
{
	
	/**
	 * Contains a list of the fields that this model uses t ostore data. 
	 * Fields are stored in a FILO way, so the earlier you register a field
	 * the further left will it be on a database table (if you look at it 
	 * in table mode).
	 * 
	 * @var Field[]
	 */
	private $fields;
	
	/**
	 * The indexes the table can use to optimize the search performance.
	 *
	 * @var Collection <Index>
	 */
	private $indexes;
	
	/**
	 * Contains a reference to the table this model is 'templating'. This 
	 * means that the current model is attached to said table and offers to 
	 * it information about the data that is stored to the DBMS and the format
	 * it should hold.
	 *
	 * @var Table 
	 */
	private $table;
	
	/**
	 * The name of the table that represents this schema on DBMS' side. This will
	 * be automatically generated from the class name and will be replacing the 
	 * invalid inverted bar (\) with hyphens (-) that are not valid as a class name.
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Creates a new instance of the Model. This allows Spitfire to create 
	 * and manage data accordingly to your wishes on a DB engine without 
	 * requiring you to integrate with any concrete engine but writing code
	 * that SF will translate.
	 * 
	 * @param string $name
	 * @param Table  $table
	 */
	final public function __construct($name, Table$table = null)
	{
		#Define the Model's table as the one just received
		$this->table   = $table;
		$this->name    = strtolower($name);
		
		#Create a field called ID that automatically identifies records 
		$this->_id = new IntegerField(true);
		$this->_id->setAutoIncrement(true);
		
		#Create a default index for the primary key
		$pk = new Index([$this->_id]);
		$pk->setPrimary(true);
		
		#Create the index collection
		$this->indexes = new Collection([$pk]);
	}
	
	/**
	 * Imports a set of fields. This allows to back them up in case they're 
	 * needed. Please note that the parent setting for them will be rewritten.
	 * 
	 * @param Field[] $fields
	 */
	public function setFields($fields)
	{
		#Loop through the fields to import them
		foreach ($fields as $field) {
			$this->{$field->getName()} = $field; #This triggers the setter
		}
	}
	
	/**
	 * Returns a logical field for this model. "Logical field" refers to fields
	 * that can also contain complex datatypes aka References. 
	 * 
	 * You can use the Field::getPhysical() method to retrieve the physical fields
	 * the application uses to interact with the DBMS.
	 * 
	 * @param string $name
	 * @return Field|null
	 */
	public function getField($name)
	{
		if (isset($this->fields[$name])) {
			return $this->fields[$name]; 
		}
		else {
			return null; 
		}
	}
	
	/**
	 * Sets a field for this schema. The field contains data that is used to generate
	 * database schemas, relations, etc.
	 * 
	 * @template T of Field 
	 * @param string $name
	 * @param T $field
	 * @return T
	 */
	public function setField(string $name, Field $field) : Field
	{
		/*
		 * First we need to check if the field already exists. In the event of us
		 * overwriting the field we need to remove it from the already existing 
		 * indexes
		 */
		if (isset($this->fields[$name])) {
			unset($this->$name);
		}
		
		$field->setName($name);
		$field->setSchema($this);
		$this->fields[$name] = $field;
		
		return $field;
	}
	
	/**
	 * Sets the field with the provided name to be an int. The database driver should only accept 
	 * int values for this field when writing to the database.
	 * 
	 * @param string $name
	 * @param boolean $unsigned
	 * @return IntegerField
	 */
	public function integer(string $name, bool $unsigned = false) : IntegerField
	{
		return $this->setField($name, new IntegerField($unsigned));
	}
	
	
	/**
	 * Causes the field with this name to only accept float values.
	 * 
	 * @param string $name
	 * @param boolean $unsigned
	 * @return FloatField
	 */
	public function float(string $name, bool $unsigned = false) : FloatField
	{
		return $this->setField($name, new FloatField($unsigned));
	}
	
	
	/**
	 * @param string $name
	 * @param int $length
	 * @return StringField
	 */
	public function string(string $name, int $length) : StringField
	{
		assert($length > 0);
		return $this->setField($name, new StringField($length));
	}
	
	
	/**
	 * @param string $name
	 * @return TextField
	 */
	public function text(string $name) : TextField
	{
		return $this->setField($name, new TextField());
	}
	
	
	/**
	 * @param string $name
	 * @param string[] $options
	 * @return EnumField
	 */
	public function enum(string $name, array $options) : EnumField
	{
		return $this->setField($name, new EnumField($options));
	}
	
	/**
	 * 
	 * @param string $name
	 * @param class-string $to
	 * @return Reference
	 */
	public function reference(string $name, string $to) : Reference
	{
		return $this->setField($name, new Reference($to));
	}
	
	/**
	 * 
	 * @param string $name
	 * @param class-string $target
	 * @param string $role
	 * @return ChildrenField
	 */
	public function children(string $name, $target, $role) : ChildrenField
	{
		return $this->setField($name, new ChildrenField($target, $role));
	}
	
	/**
	 * 
	 * @param string $name
	 * @param class-string $target
	 * @return ManyToManyField
	 */
	public function many(string $name, $target) : ManyToManyField
	{
		return $this->setField($name, new ManyToManyField($target));
	}
	
	/**
	 * Returns the whole list of fields this model contains. This are logical fields
	 * and therefore can contain data that is too complex to be stored directly
	 * by a DB Engine, the table object is in charge of providing a list of 
	 * DB Friendly fields.
	 * 
	 * @return Field[]
	 */
	public function getFields()
	{
		return $this->fields;
	}
	
	/**
	 * Returns the 'name' of the model. The name of a model is obtained by 
	 * removing the Model part of tit's class name. It's best practice to 
	 * avoid the usage of this function for anything rather than logging.
	 * 
	 * This function has a special use case, it also defines the name of the
	 * future table. By changing this you change the table this model uses
	 * on DBMS, this is particularly useful when creating multiple models
	 * that refer to a single dataset like 'People' and 'Adults'.
	 * 
	 * @staticvar string $name
	 * @return string
	 */
	final public function getName()
	{
		return $this->name;
	}
	
	/**
	 * Returns the tablename spitfire considers best for this Model. This 
	 * value is calculated by using the Model's name and replacing any 
	 * <b>\</b> with hyphens to make the name database friendly.
	 * 
	 * Hyphens are the only chars that DBMS tend to accept that class names
	 * do not. So this way we avoid any colissions in names that could be 
	 * coincidentally similar.
	 * 
	 * @return string
	 */
	public function getTableName()
	{
		return trim(str_replace('\\', '-', $this->getName()), '-_ ');
	}
	
	/**
	 * Returns the table the Schema represents. The schema is the logical representation
	 * of a Table. While the Schema will manage logical fields that the programmer
	 * can directly write data to, the Table will take that data and translate it
	 * so the database engine can use it.
	 * 
	 * @return Table
	 */
	public function getTable()
	{
		return $this->table;
	}
	
	/**
	 * Sets the table this schema manages. This connection is used to determine 
	 * what DBMS table it should address and to make correct data conversion.
	 * 
	 * @param Table $table
	 */
	public function setTable($table)
	{
		$this->table = $table;
	}
	
	/**
	 * Extending this function on models allows you to add restrictions to a 
	 * query when this is made for this model. This way you can generate a 
	 * behavior similar to a view where the records are always limited by 
	 * the restriction set in this function.
	 * 
	 * This is especially useful when fake deleting records from the database.
	 * You use a flag to indicate a record is deleted and use this function
	 * to hide any records that have that flag.
	 * 
	 * @todo Move to the model. It makes no sense having it here
	 * @param Query $query The query that is being prepared to be executed.
	 * @return type
	 */
	public function getBaseRestrictions(Query$query)
	{
		//Do nothing, this is meant for overriding
	}
	
	public function index()
	{
		$fields = func_get_args();
		$index = new Index($fields);
		
		$this->indexes->push($index);
		return $index;
	}
	
	/**
	 * Returns the collection of indexes that are contained in this model.
	 * 
	 * @return Collection <Index>
	 */
	public function getIndexes()
	{
		return $this->indexes;
	}
	
	/**
	 * Returns a list of fields which compound the primary key of this model.
	 * The primary key is a set of records that identify a unique record.
	 * 
	 * @return Index
	 */
	public function getPrimary()
	{
		#Fetch the field list
		$indexes = $this->indexes;
		
		#Loop over the indexes and get the primary one
		foreach ($indexes as $index) {
			if ($index->isPrimary()) {
				return $index; 
			}
		}
		
		#If there was no index, then return a null value
		return null;
	}
	
	/**
	 * The getters and setters for this class allow us to create fields with
	 * a simplified syntax and access them just like they were properties
	 * of the object. Please note that some experts recommend avoiding magic
	 * methods for performance reasons. In this case you can use the field()
	 * method.
	 * 
	 * @param string $name
	 * @param Field  $value
	 */
	public function __set($name, $value) 
	{
		$this->setField($name, $value);
	}
	
	/**
	 * The getters and setters for this class allow us to create fields with
	 * a simplified syntax and access them just like they were properties
	 * of the object. Please note that some experts recommend avoiding magic
	 * methods for performance reasons. In this case you can use the field()
	 * method.
	 * 
	 * @param string $name
	 * @throws PrivateException
	 * @return Field
	 */
	public function __get($name)
	{
		if (isset($this->fields[$name])) {
			return $this->fields[$name]; 
		}
		else {
			throw new PrivateException('Schema: No field ' . $name . ' found'); 
		}
	}
	
	/**
	 * Removes a field from the Schema. This is a somewhat rare method, since you
	 * should avoid it's usage in production environments and you should REALLY 
	 * know what you're doing before using it.
	 * 
	 * @param string $name
	 * @throws PrivateException
	 */
	public function __unset($name)
	{
		#Check if the field actually exists.
		if (!isset($this->fields[$name])) {
			throw new PrivateException('Schema: Could not delete. No field ' . $name . ' found');
		}
		
		#Get the field
		$f = $this->fields[$name];
		unset($this->fields[$name]);
		
		#Find an index that may contain the field and remove it too
		$this->indexes = $this->indexes->filter(function ($e) use ($f) {
			return !$e->contains($f);
		});
	}
}
