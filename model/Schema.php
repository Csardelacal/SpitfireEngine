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
	 * Contains a reference to the table this model is 'templating'. This
	 * means that the current model is attached to said table and offers to
	 * it information about the data that is stored to the DBMS and the format
	 * it should hold.
	 *
	 * @var Table
	 */
	private $table;
	
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
}
