<?php namespace spitfire\model;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */


use JsonSerializable;
use ReflectionClass;
use ReflectionException;
use spitfire\collection\Collection;
use spitfire\collection\OutOfBoundsException;
use spitfire\exceptions\ApplicationException;
use spitfire\model\attribute\Table as TableAttribute;
use spitfire\model\relations\RelationshipContent;
use spitfire\model\utils\ModelHydrator;
use spitfire\provider\NotFoundException as ProviderNotFoundException;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\events\RecordBeforeDeleteEvent;
use spitfire\storage\database\events\RecordBeforeUpdateEvent;
use spitfire\storage\database\events\RecordEvent;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Record;
use spitfire\storage\database\Schema as DatabaseSchema;
use spitfire\utils\Mixin;
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
	
	use Mixin;
	
	/**
	 * The actual data that the record contains. The record is basically a wrapper
	 * around the array that allows to validate data on the go and to alert the
	 * programmer about inconsistent types.
	 *
	 * @var ActiveRecord<self>|null
	 */
	private $record;
	
	/**
	 * Determines whether the model is holding a record or not.
	 *
	 * @var bool
	 */
	private $hydrated = false;
	
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
	 * Returns the activerecord this model is working on. This requires the model to be
	 * hydrated to work.
	 *
	 * @return ActiveRecord<self>
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
	 * Creates a copy of the model that is hydrated with the given record. This
	 * allows the application to distinguish between models that carry a payload
	 * and the ones that provide relationships and schema information.
	 *
	 * @param ActiveRecord<self> $record
	 * @return self
	 */
	public function withHydrate(ActiveRecord $record) : self
	{
		assert(!$this->hydrated);
		assert($record->getModel()->getClassname() === $this::class);
		$copy = clone $this;
		$copy->record = $record;
		$copy->hydrated = true;
		$copy->rehydrate();
		$copy->mixin($record);
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
		
		/**
		 * It's impossible that the class does not exists if we're using an instance of it
		 * to work with it.
		 * 
		 * @throws void
		 */
		$reflection = new ReflectionModel($this::class);
		
		foreach ($keys as $k) {
			if (!$reflection->hasProperty($k)) {
				continue;
			}
			
			try {
				/**
				 * @throws void We already check the property exists
				 */
				$property = $reflection->getProperty($k);
				$value = $property->getValue($this);
				
				if ($value instanceof Model) {
					$value = new RelationshipContent(true, new Collection($value));
				}
				elseif ($value instanceof Collection) {
					$value = new RelationshipContent(false, $value);
				}
				elseif ($value === null && $reflection->getRelationShips()->has($k)) {
					/**
					 * @var Collection<Model>
					 */
					$content = new Collection();
					$value = new RelationshipContent(true, $content);
				}
				
				/**
				 * Sanity check. The model can only hold scalars, nulls and other Models.
				 */
				assert(
					$value === null ||
					is_scalar($value) ||
					$value instanceof RelationshipContent
				);
				
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
	 * @param string[] $options
	 */
	public function store(array $options = []) : void
	{
		$this->sync();
		
		assert($this->hydrated);
		assert($this->record !== null);
		$this->record->store($options);
		
		$this->rehydrate();
		
	}
	
	/**
	 * The value of the primary key, null means that the software expects the
	 * database to assign this record a primary key on insert.
	 *
	 * When editing the primary key value this will ALWAYS return the data that
	 * the system assumes to be in the database.
	 *
	 * @return scalar
	 */
	public function getPrimary()
	{
		$primary = $this->getTable()->getPrimaryKey();
		assert($primary !== null);
		
		assert($this->hydrated);
		assert($this->record !== null);
		
		$fields = $primary->getFields();
		
		assert(!$fields->isEmpty());
		
		$result = $this->record->get($fields[0]->getName());
		
		assert($result !== null);
		assert(is_scalar($result));
		
		return $result;
	}
	
	/**
	 * Returns the values of the fields included in this records primary
	 * fields
	 *
	 * @todo Find better function name
	 * @deprecated
	 * @return (scalar|Model|null)[]
	 */
	public function getPrimaryData()
	{
		assert($this->hydrated);
		assert($this->record !== null);
		
		$primary = $this->getTable()->getPrimaryKey();
		assert($primary !== null);
		
		$primaryFields = $primary->getFields();
		$ret = array();
		
		foreach ($primaryFields as $field) {
			/**
			 * Get the record. Children cannot be the priimary key of a table.
			 */
			$_ = $this->record->get($field->getName());
			assert(!($_ instanceof Collection));
			
			$ret[$field->getName()] = $_;
		}
		
		return $ret;
	}
	
	/**
	 * 
	 * @deprecated
	 * @see ReflectionModel::query
	 * @throws ProviderNotFoundException
	 * @return QueryBuilder<self>
	 */
	public function query() : QueryBuilder
	{
		trigger_error('Calling Model::query directly is discouraged', E_USER_DEPRECATED);
		return (new QueryBuilder(
			spitfire()->provider()->get(ConnectionInterface::class), 
			new ReflectionModel(self::class)))->withDefaultMapping();
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
		/**
		 * @todo Needs caching
		 */
		$reflection = new ReflectionModel($this::class);
		
		$schema = $this->getConnection()->getSchema();
		$tablename = $reflection->getTableName();
		
		assert($schema->hasLayoutByName($tablename));
		
		/**
		 * @throws void
		 */
		return $schema->getLayoutByName($tablename);
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
	
	/**
	 * @return scalar|null|Model|Collection<Model>
	 */
	protected function lazy(string $field)
	{
		assert($this->hydrated);
		assert($this->record !== null);
		assert($this->record->has($field));
		return $this->record->get($field);
	}
	
	public function __isset(string $name) : bool
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
	
	/**
	 * @param string[] $options 
	 */
	public function delete(array $options = []) : void
	{
		$this->sync();
		
		assert($this->hydrated);
		assert($this->record !== null);
		
		#Tell the table that the record is being deleted
		$event = new RecordBeforeDeleteEvent(
			$this->getConnection(),
			$this->getTable(),
			$this->record->getUnderlyingRecord(),
			$options
		);
		$fn = function (RecordEvent $event) {
			#The insert function is in this closure, which allows the event to cancel deleting the data
			$this->getConnection()->delete($this->getTable(), $event->getRecord());
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
	 * @return scalar[]
	 */
	public function jsonSerialize() : array
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
	 *
	 * @deprecated
	 * @see ReflectionModel::getTablename
	 */
	final public static function getTableName() : string
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
	
	public function getConnection() : ConnectionInterface
	{
		assert($this->record !== null);
		return $this->record->getConnection();
	}
	
	/**
	 * @param mixed[] $args
	 * @return mixed
	 */
	public function __call(string $name, array $args)
	{
		assert($this->record !== null);
		
		$reflection = $this->record->getModel();
		
		if ($reflection->getRelationShips()->has($name)) {
			return $reflection
				->getRelationShips()[$name]
				->newInstance()
				->startQueryBuilder($this->record);
		}
	}
}
