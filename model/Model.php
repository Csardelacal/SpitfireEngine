<?php namespace spitfire\model;

use Exception;
use JsonSerializable;
use ReflectionClass;
use Serializable;
use spitfire\collection\Collection;
use spitfire\exceptions\PrivateException;
use spitfire\model\Schema;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\mysqlpdo\Driver;
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
abstract class Model implements Serializable, JsonSerializable
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
	
	private $prefix = null;
	
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
	 * Returns the record this model is working on. This requires the model to be 
	 * hydrated to work.
	 * 
	 * @return Model
	 */
	public function setRecord(Record $record) : Model
	{
		$this->record = $record;
		return $this;
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
		return $this->record->raw();
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
		$this->onbeforesave();
		
		#Decide whether to insert or update depending on the Model
		if ($this->record->exists()) {
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
	 * Returns the values of the fields included in this records primary
	 * fields
	 *
	 * @todo Find better function name
	 * @return array
	 */
	public function getPrimaryData()
	{
		$primaryFields = $this->getTable()->getPrimaryKey()->getFields();
		$ret = array();
		
		foreach ($primaryFields as $field) {
			$ret[$field->getName()] = $this->record->get($field->getName());
		}
		
		return $ret;
	}
	
	public function query()
	{
		return new QueryBuilder($this);
	}
	
	public function get($name)
	{
		if (!array_key_exists($name, $this->record)) {
			throw new Exception('Bad');
		}
		assert(array_key_exists($name, $this->record));
		return $this->record[$name];
	}
	
	public function getQuery()
	{
		$query     = $this->getTable()->getDb()->getObjectFactory()->queryInstance($this->getTable());
		$primaries = $this->table->getModel()->getPrimary()->getFields();
		
		foreach ($primaries as $primary) {
			$name = $primary->getName();
			$query->addRestriction($name, $this->$name);
		}
		
		return $query;
	}
	
	/**
	 * Returns the table this record belongs to.
	 *
	 * @return Layout
	 */
	public function getTable() : Layout
	{
		return $this->getSchema()->getLayoutByName($this->getTableName());
	}
	
	/**
	 * By default, spitfire 
	 */
	public function getSchema() : DatabaseSchema
	{
		return spitfire()->provider()->get(DatabaseSchema::class);
	}
	
	public function getPrefix()
	{
		return $this->prefix;
	}
	
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}
	
	public function getTableName()
	{
		$reflection = new ReflectionClass($this);
		return Strings::plural($reflection->getShortName());
	}
	
	public function __set($field, $value)
	{
		
		if (!isset($this->record[$field])) {
			throw new PrivateException("Setting non existent field: " . $field);
		}
		
		$this->record[$field]->usrSetData($value);
	}
	
	public function __get($field)
	{
		#If the field is in the record we return it's contents
		if (isset($this->record[$field])) {
			return $this->record[$field]->usrGetData();
		} else {
			//TODO: In case debug is enabled this should throw an exception
			return null;
		}
	}
	
	public function __isset($name)
	{
		return (array_key_exists($name, $this->record));
	}
	
	//TODO: This now breaks due to the adapters
	public function serialize()
	{
		$data = array();
		foreach ($this->record as $adapter) {
			if (! $adapter->isSynced()) {
				throw new PrivateException("Database record cannot be serialized out of sync");
			}
			$data = array_merge($data, $adapter->dbGetData());
		}
		
		$output = array();
		$output['model'] = $this->table->getModel()->getName();
		$output['data']  = $data;
		
		return serialize($output);
	}
	
	public function unserialize($serialized)
	{
		
		$input = unserialize($serialized);
		$this->table = db()->table($input['model']);
		
		$this->makeAdapters();
		$this->populateAdapters($input['data']);
	}
	
	public function __toString()
	{
		return sprintf('%s(%s)', $this->getTable()->getModel()->getName(), implode(',', $this->getPrimaryData()));
	}
	
	public function delete(array $options = [])
	{
		#Tell the table that the record is being deleted
		$event = new RecordBeforeUpdateEvent($this->getConnection(), $this->record, $options);
		$fn = function (Record $record) {
			#The insert function is in this closure, which allows the event to cancel storing the data
			$this->getConnection()->update($record);
		};
		
		$this->getTable()->events()->dispatch($event, $fn);
		$this->rehydrate();
	}
	
	protected function makeAdapters()
	{
		#If there is no table defined there is no need to create adapters
		if ($this->table === null) {
			return;
		}
		
		$fields = $this->getTable()->getModel()->getFields();
		foreach ($fields as $field) {
			$this->record[$field->getName()] = $field->getAdapter($this);
		}
	}
	
	protected function populateAdapters($data)
	{
		#If the set carries no data, why bother reading?
		if (empty($data)) {
			return;
		}
		
		#Retrieves the full list of fields this adapter needs to populate
		$fields = $this->getTable()->getModel()->getFields();
		
		#Loops through the fields retrieving the physical fields
		foreach ($fields as $field) {
			$physical = $field->getPhysical();
			$current  = array();
			
			#The physical fields are matched to the content and it is assigned.
			foreach ($physical as $p) {
				$current[$p->getName()] = $data[$p->getName()];
			}
			
			#Set the data into the adapter and let it work it's magic.
			$this->record[$field->getName()]->dbSetData($current);
		}
	}
	
	/**
	 *
	 * @deprecated since version 0.1-dev 20190611
	 * @return Collection
	 */
	public function getDependencies()
	{
		
		$dependencies = collect($this->record)
			->each(function ($e) {
				return $e->getDependencies();
			})
			->filter()
			->flatten();
		
		return $dependencies;
	}
	
	public function setPivot(Model $pivot) : Model
	{
		$this->pivot = $pivot;
		return $this;
	}
	
	/**
	 * 
	 * @return Model|null
	 */
	public function pivot() :? Model
	{
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
	 * Allows the model to perform small tasks before it is written to the database.
	 *
	 * @return void This method does not return
	 */
	public function onbeforesave()
	{
	}
	
	public function getConnection() : DatabaseDriverInterface
	{
		return $this->connection !== null?
			spitfire()->provider()->get(ConnectionManager::class)->get($this->connection) :
			spitfire()->provider()->get(ConnectionManager::class)->getDefault();
	}
}
