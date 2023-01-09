<?php namespace spitfire\model;

use JsonSerializable;
use ReflectionClass;
use ReflectionException;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\model\attribute\Table as TableAttribute;
use spitfire\model\relations\RelationshipContent;
use spitfire\model\utils\ModelHydrator;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\events\RecordBeforeInsertEvent;
use spitfire\storage\database\events\RecordBeforeUpdateEvent;
use spitfire\storage\database\Field as DatabaseField;
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
	 * @var ActiveRecord|null
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
	 * @var ConnectionInterface
	 */
	private ConnectionInterface $connection;
	
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
	
	public function __construct(ConnectionInterface $connection)
	{
		$this->connection = $connection;
	}
	
	/**
	 * Returns the activerecord this model is working on. This requires the model to be
	 * hydrated to work.
	 *
	 * @return ActiveRecord
	 */
	public function getActiveRecord() : ActiveRecord
	{
		assert($this->record !== null);
		return $this->record;
	}
	
	/**
	 * Returns the record this model is working on. This requires the model to be
	 * hydrated to work.
	 *
	 * @return Record
	 */
	public function getRecord() : Record
	{
		assert($this->record !== null);
		return $this->record->getUnderlyingRecord();
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
		assert($this->record !== null);
		return $this->record->raw();
	}
	
	/**
	 * More often than not, the system will wish to hydrate a database record
	 * directly. This gets very verbose, this function just hydrates a model
	 * with an active record for this model
	 *
	 * @return self
	 */
	public function withSelfHydrate(Record $record) : self
	{
		return $this->withHydrate(new ActiveRecord($this, $record));
	}
	
	/**
	 * Creates a copy of the model that is hydrated with the given record. This
	 * allows the application to distinguish between models that carry a payload
	 * and the ones that provide relationships and schema information.
	 *
	 * @return self
	 */
	public function withHydrate(ActiveRecord $record) : self
	{
		assert(!$this->hydrated);
		assert($record->getModel() === $this);
		$copy = clone $this;
		$copy->record = $record;
		$copy->hydrated = true;
		$copy->rehydrate();
		return $copy;
	}
	
	/**
	 * Rehydrating reads the data from the underlying record into the model. This allows
	 * the model to use properties properly and provides PHP with native behacviors.
	 *
	 * @todo This prevents models from using inheritance, we currently are using traits for
	 * most of the model extensions. Extending one model from another could have applications
	 * in future revisions, but the use case currently doesn't justify the complexity.
	 *
	 * @see https://3v4l.org/rGs73
	 */
	public function rehydrate() : void
	{
		assert($this->hydrated);
		assert($this->record !== null);
		ModelHydrator::hydrate($this);
	}
	
	/**
	 * This performs the opposite operation to rehydrating, it writes data from the model
	 * into the record so it can be written to the DBMS.
	 *
	 * @todo This needs to work for private properties too.
	 */
	public function sync() : void
	{
		assert($this->hydrated);
		assert($this->record !== null);
		
		/**
		 * Prepare the raw data and the reflection we need to perform the sync.
		 */
		$keys = $this->record->keys();
		$reflection = new ReflectionClass($this);
		
		foreach ($keys as $k) {
			try {
				$property = $reflection->getProperty($k);
				$value = $property->getValue($this);
				
				if ($value instanceof Model) {
					$value = new RelationshipContent(true, new Collection($value));
				}
				elseif ($value instanceof Collection) {
					$value = new RelationshipContent(false, $value);
				}
				
				$this->record->set($k, $value);
			}
			/**
			 *
			 * @see Model::rehydrate()
			 */
			catch (ReflectionException $e) {
				trigger_error(sprintf('Model is missing property %s', $k), E_USER_NOTICE);
			}
		}
	}
	
	/**
	 * This method stores the data of this record to the database. In case
	 * of database error it throws an Exception and leaves the state of the
	 * record unchanged.
	 *
	 * @throws ApplicationException
	 */
	public function store(array $options = [])
	{
		$this->sync();
		
		assert($this->hydrated);
		assert($this->record !== null);
		
		$primary = $this->getTable()->getPrimaryKey()->getFields()->first();
		assert($primary instanceof DatabaseField);
		
		/**
		 * If the primary key is assumed to be null on the dbms (which is not possible
		 * the way we designed SF), the system will assume that the record does not exist
		 * on the DBMS and therefore record a new entry in the table.
		 */
		if ($this->record->get($primary->getName()) === null) {
			#Tell the table that the record is being stored
			$event = new RecordBeforeInsertEvent(
				$this->getConnection(),
				$this->getTable(),
				$this->record->getUnderlyingRecord(),
				$options
			);
			$fn = function (RecordBeforeInsertEvent $event) {
				$record = $event->getRecord();
				#The insert function is in this closure, which allows the event to cancel storing the data
				$this->getConnection()->insert($this->getTable(), $record);
			};
		}
		else {
			#Tell the table that the record is being stored
			$event = new RecordBeforeUpdateEvent(
				$this->getConnection(),
				$this->getTable(),
				$this->record->getUnderlyingRecord(),
				$options
			);
			$fn = function (RecordBeforeUpdateEvent $event) {
				$record = $event->getRecord();
				#The insert function is in this closure, which allows the event to cancel storing the data
				$this->getConnection()->update($this->getTable(), $record);
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
	 * @return int|float|string
	 */
	public function getPrimary()
	{
		assert($this->hydrated);
		assert($this->record !== null);
		
		$fields = $this->getTable()->getPrimaryKey()->getFields();
		
		if ($fields->isEmpty()) {
			throw new ApplicationException('Record has no primary key', 2101181306);
		}
		
		$result = $this->record->get($fields[0]->getName());
		
		assert($result !== null);
		assert(!($result instanceof RelationshipContent));
		
		return $result;
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
		assert($this->record !== null);
		
		$primaryFields = $this->getTable()->getPrimaryKey()->getFields();
		$ret = array();
		
		foreach ($primaryFields as $field) {
			$ret[$field->getName()] = $this->record->get($field->getName());
		}
		
		return $ret;
	}
	
	public function query() : QueryBuilder
	{
		return (new QueryBuilder($this))->withDefaultMapping();
	}
	
	/**
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function get(string $name)
	{
		assert($this->hydrated);
		assert($this->record !== null);
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
	
	protected function lazy(string $field)
	{
		assert($this->hydrated);
		assert($this->record !== null);
		assert($this->record->has($field));
		return $this->record->get($field);
	}
	
	public function __isset($name)
	{
		assert($this->hydrated);
		assert($this->record !== null);
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
		assert($this->hydrated);
		assert($this->record !== null);
		
		$this->sync();
		
		#Tell the table that the record is being deleted
		$event = new RecordBeforeUpdateEvent(
			$this->getConnection(),
			$this->getTable(),
			$this->record->getUnderlyingRecord(),
			$options
		);
		$fn = function (Record $record) {
			#The insert function is in this closure, which allows the event to cancel storing the data
			$this->getConnection()->update($this->getTable(), $record);
		};
		
		$this->getTable()->events()->dispatch($event, $fn);
		$this->rehydrate();
	}
	
	public function setPivot(Model $pivot) : Model
	{
		assert($this->hydrated);
		assert($this->record !== null);
		
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
		assert($this->record !== null);
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
	public function jsonSerialize() : mixed
	{
		assert($this->hydrated);
		assert($this->record !== null);
		
		$raw = [];
		
		foreach ($this->record->raw() as $name => $value) {
			$raw[$name] = $value;
		}
		
		return $raw;
	}
	
	/**
	 * @todo This should return a database connection
	 */
	public function getConnection() : ConnectionInterface
	{
		return $this->connection;
	}
	
	final public static function getTableName()
	{
		$reflection = new ReflectionClass(static::class);
		$tableAttribute = $reflection->getAttributes(TableAttribute::class);
		assert(count($tableAttribute) <= 1);
		
		if (!empty($tableAttribute)) {
			return $tableAttribute[0]->newInstance()->getName();
		}
		else {
			return Strings::plural(Strings::snake(Strings::rTrimString($reflection->getShortName(), 'Model')));
		}
	}
}
