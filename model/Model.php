<?php namespace spitfire\model;

use Exception;
use JsonSerializable;
use ReflectionClass;
use Serializable;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\PrivateException;
use spitfire\storage\database\Connection;
use spitfire\storage\database\DriverInterface as DatabaseDriverInterface;
use spitfire\storage\database\events\RecordBeforeInsertEvent;
use spitfire\storage\database\events\RecordBeforeUpdateEvent;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Record;
use spitfire\storage\database\Schema as DatabaseSchema;
use spitfire\utils\Strings;

/**
 * This class allows to track changes on database data along the use of a program
 * and creates interactions with the database in a safe way.
 *
 * @todo Make this class implement Iterator
 * @author César de la Cal <cesar@magic3w.com>
 */
abstract class Model implements JsonSerializable
{
	
	/**
	 * The actual data that the record contains. The record is basically a wrapper
	 * around the array that allows to validate data on the go and to alert the
	 * programmer about inconsistent types.
	 *
	 * @var Record|null
	 */
	private $record;
	
	/**
	 * Determines whether the model is holding a record or not.
	 * 
	 * @var bool
	 */
	private $hydrated = false;
	
	/**
	 * 
	 * @var string|null
	 */
	private $connection = null;
	
	private $prefix = '';
	
	/**
	 * If the model was retrieved as part of a relationship, it's possible that it
	 * is enriched by the pivot data.
	 * 
	 * This for example could be something like a user and follower table. In that case
	 * we could retrieve a user's followers, this would return a usermodel with the
	 * follower model as a pivot, which provides information about the relationship like
	 * when the following was started, etc.
	 * 
	 * @var Model|null
	 */
	private $pivot = null;
	
	/**
	 * Returns the record this model is working on. This requires the model to be 
	 * hydrated to work.
	 * 
	 * @return Record
	 */
	public function getRecord() : Record
	{
		assert($this->record !== null);
		return $this->record;
	}
	
	/**
	 * Returns the data this record currently contains as associative array.
	 * Remember that this data COULD be invalid when using setData to provide
	 * it.
	 *
	 * @return mixed
	 */
	public function getData()
	{
		assert($this->hydrated);
		return $this->record->raw();
	}
	
	/**
	 * Creates a copy of the model that is hydrated with the given record. This
	 * allows the application to distinguish between models that carry a payload
	 * and the ones that provide relationships and schema information.
	 * 
	 * @return self
	 */
	public function withHydrate(Record $record) : Model
	{
		$copy = clone $this;
		$copy->record = $record;
		$copy->hydrated = true;
		$copy->rehydrate();
		return $copy;
	}
	
	/**
	 * Rehydrating reads the data from the underlying record into the model. This allows
	 * the model to use properties properly and provides PHP with native behacviors.
	 */
	public function rehydrate() : void
	{
		$raw = $this->record->raw();
		
		foreach ($raw as $k => $v) {
			$this->$k = $v;
		}
	}
	
	/**
	 * This performs the opposite operation to rehydrating, it writes data from the model
	 * into the record so it can be written to the DBMS.
	 */
	public function sync() : void
	{
		assert($this->hydrated);
		$raw = $this->record->raw();
		
		foreach (array_keys($raw) as $k) {
			$this->record->set($k, $this->$k);
		}
	}
	
	/**
	 * This method stores the data of this record to the database. In case
	 * of database error it throws an Exception and leaves the state of the
	 * record unchanged.
	 *
	 * @throws PrivateException
	 */
	public function store(array $options = [])
	{
		$this->sync();
		
		/**
		 * If the primary key is assumed to be null on the dbms (which is not possible
		 * the way we designed SF), the system will assume that the record does not exist
		 * on the DBMS and therefore record a new entry in the table.
		 */
		if ($this->record->getPrimary() === null) {
			#Tell the table that the record is being stored
			$event = new RecordBeforeInsertEvent($this->getConnection(), $this->record, $options);
			$fn = function (Record $record) {
				#The insert function is in this closure, which allows the event to cancel storing the data
				$this->getConnection()->insert($record);
			};
		}
		else {
			#Tell the table that the record is being stored
			$event = new RecordBeforeUpdateEvent($this->getConnection(), $this->record, $options);
			$fn = function (Record $record) {
				#The insert function is in this closure, which allows the event to cancel storing the data
				$this->getConnection()->update($record);
			};
		}
		
		$this->getTable()->events()->dispatch($event, $fn);
		$this->rehydrate();
	}
	
	/**
	 * The value of the primary key, null means that the software expects the
	 * database to assign this record a primary key on insert.
	 * 
	 * When editing the primary key value this will ALWAYS return the data that 
	 * the system assumes to be in the database.
	 * 
	 * @return int|string
	 */
	public function getPrimary()
	{
		assert($this->hydrated);
		
		$fields = $this->getTable()->getPrimaryKey()->getFields();
		
		if ($fields->isEmpty()) {
			throw new ApplicationException('Record has no primary key', 2101181306); 
		}
		
		return $this->record->get($fields[0]->getName());
	}
	
	/**
	 * Returns the values of the fields included in this records primary
	 * fields
	 *
	 * @todo Find better function name
	 * @deprecated
	 * @return array
	 */
	public function getPrimaryData()
	{
		assert($this->hydrated);
		
		$primaryFields = $this->table->getPrimaryKey()->getFields();
		$ret = array();
		
		foreach ($primaryFields as $field) {
			return $this->record[$field->getName()];
		}
		
		return $ret;
	}
	
	public function query() : QueryBuilder
	{
		return new QueryBuilder($this->getConnection(), $this);
	}
	
	/**
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function get(string $name)
	{
		assert($this->hydrated);
		return $this->record->get($name);
	}
	
	/**
	 * Returns the table this record belongs to.
	 *
	 * @return Layout
	 */
	public function getTable() : Layout
	{
		return $this->getConnection()->getSchema()->getLayoutByName($this->getTableName());
	}
	
	/**
	 * By default, spitfire 
	 * 
	 * @deprecated I don't even
	 */
	public function getSchema() : DatabaseSchema
	{
		return $this->getConnection()->getSchema();
	}
	
	public function getPrefix() : string
	{
		return $this->prefix;
	}
	
	public function setPrefix(string $prefix)
	{
		$this->prefix = $prefix;
	}
	
	public function getTableName()
	{
		$reflection = new ReflectionClass($this);
		return $this->prefix . Strings::plural($reflection->getShortName());
	}
	
	public function __set(string $field, $value)
	{
		assert($this->hydrated);
		assert($this->record->has($field));
		$this->record->set($field, $value);
	}
	
	public function __get($field)
	{
		assert($this->hydrated);
		assert($this->record->has($field));
		return $this->record->get($field);
	}
	
	public function __isset($name)
	{
		assert($this->hydrated);
		return $this->record->has($name);
	}
	
	
	public function __toString()
	{
		if ($this->hydrated) {
			return sprintf('%s(%s)', $this->getTableName(), $this->getPrimary());
		}
		
		return sprintf('Model (%s, %s)', get_class($this), $this->getTableName());
	}
	
	public function delete(array $options = [])
	{
		$this->sync();
		
		#Tell the table that the record is being deleted
		$event = new RecordBeforeUpdateEvent($this->getConnection(), $this->record, $options);
		$fn = function (Record $record) {
			#The insert function is in this closure, which allows the event to cancel storing the data
			$this->getConnection()->update($record);
		};
		
		$this->getTable()->events()->dispatch($event, $fn);
		$this->rehydrate();
	}
	
	public function setPivot(Model $pivot) : Model
	{
		assert($this->hydrated);
		
		$this->pivot = $pivot;
		return $this;
	}
	
	/**
	 * 
	 * @return Model|null
	 */
	public function pivot() :? Model
	{
		assert($this->hydrated);
		return $this->pivot;
	}
	
	/**
	 * The jsonserialize endpoint allows applications to json_encode this model without
	 * having to loop over all the keys.
	 *
	 * If your application wishes to not expose certain data to the outside world, feel
	 * free to implement jsonSerialize in your model and unset the keys you do not wish
	 * to broadcast.
	 *
	 * This method is intended to aid passing data to views and rendering output, the data
	 * exported here cannot be imported back into spitfire.
	 *
	 * @return array
	 */
	public function jsonSerialize()
	{
		$raw = [];
		
		foreach ($this->record as $name => $adapter) {
			$raw[$name] = $adapter->usrGetData();
		}
		
		return $raw;
	}
	
	/**
	 * @todo This should return a database connection
	 */
	public function getConnection() : Connection
	{
		return $this->connection !== null?
			spitfire()->provider()->get(ConnectionManager::class)->get($this->connection) :
			spitfire()->provider()->get(ConnectionManager::class)->getDefault();
	}
	
	public function setConnection(string $id) : Model
	{
		$this->connection = $id;
		return $this;
	}
}
