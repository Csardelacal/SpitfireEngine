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


use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\exceptions\ApplicationException;
use spitfire\model\relations\RelationshipContent;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\events\RecordBeforeInsertEvent;
use spitfire\storage\database\events\RecordBeforeUpdateEvent;
use spitfire\storage\database\Record;
use spitfire\utils\Mixin;
use spitfire\storage\database\Field as DatabaseField;
use spitfire\storage\database\IndexInterface;

/**
 * The surrogate acts as a bridge between model and record, it implements lazy
 * loading whenever needed and manages the conversion between raw data in the
 * model and the physical data in the Record.
 *
 * @todo Add a cache that maintains the data that the surrogate contains.
 *
 * @template T of Model
 * @method string[] raw()
 * @method string[] keys()
 *
 * @mixin Record
 */
class ActiveRecord
{
	
	use Mixin;
	
	/**
	 * The active record is aware of the connection to the database and will help
	 * managing data on the server if needed.
	 * 
	 * @var ConnectionInterface
	 */
	private ConnectionInterface $connection;
	
	/**
	 * The model provides us with information about the relationships
	 *
	 * @var ReflectionModel<T>
	 */
	private ReflectionModel $model;
	
	/**
	 * The record contains the data we need to work with.
	 *
	 * @var Record
	 */
	private Record $record;
	
	/**
	 *
	 * @todo This should be a collection of promises for models.
	 * @var Collection<RelationshipContent>
	 */
	private Collection $cache;
	
	/**
	 * 
	 * @param ReflectionModel<T> $model
	 */
	public function __construct(ConnectionInterface $connection, ReflectionModel $model, Record $record)
	{
		$this->model = $model;
		$this->record = $record;
		$this->connection = $connection;
		$this->cache  = new TypedCollection(RelationshipContent::class);
		$this->mixin($record);
	}
	
	/**
	 * Returns the current data for the provided field. During development, with assertions,
	 * this method will fail when attempting to read a non-existing field.
	 *
	 * @param string $field
	 * @return scalar|null|Model|Collection<Model>
	 */
	public function get(string $field)
	{
		/**
		 * If the model has a relationship for this field, we will proceed to lazy load the model.
		 */
		if ($this->model->getRelationShips()->has($field)) {
			if ($this->cache->has($field)) {
				$pl = $this->cache[$field];
				return $pl->isSingle()? $pl->getPayload()->first() : $pl->getPayload();
			}
		}
		
		return $this->record->get($field)?? null;
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
		$schema = $this->connection->getSchema();
		$tablename = $this->model->getTableName();
		
		assert($schema->hasLayoutByName($tablename));
		
		/**
		 * @throws void
		 */
		$table  = $schema->getLayoutByName($tablename);
		$primary = $table->getPrimaryKey();
		
		assert($primary instanceof IndexInterface);
		$fields = $primary->getFields();
		
		assert(!$fields->isEmpty());
		
		$_ = $this->record->get($fields[0]->getName());
		
		assert(!is_bool($_));
		assert($_ !== null);
		
		return $_;
	}
	
	/**
	 * Indicates whether the aactive record can accept or provide data for a field
	 * with the provided name. This can be due to two reasons.
	 * 
	 * 1. The relation contains a field with the name
	 * 2. The model contains a relationship with that name
	 * 
	 * @param string $fieldname
	 * @return bool
	 */
	public function has(string $fieldname) : bool
	{
		return $this->record->has($fieldname) || $this->model->getRelationShips()->has($fieldname);
	}
	
	/**
	 * Returns a list of available keys for the active record (this includes the 'real'
	 * data on the DBMS and the relationships on top).
	 * 
	 * @todo It would be way more elegant if the relationship collection would return the keys instead
	 * of having to conveert it to an array first
	 * @return string[]
	 */
	public function keys() : array
	{
		return array_merge(
			$this->record->keys(),
			array_keys($this->model->getRelationShips()->toArray())
		);
	}
	
	/**
	 * Sets a field to a given value.
	 *
	 * @param string $field
	 * @param scalar|RelationshipContent|null $value
	 * @return ActiveRecord<T>
	 */
	public function set(string $field, $value) : ActiveRecord
	{
		assert($this->has($field), sprintf('Record does not have expected field %s', $field));
		
		if ($this->cache->has($field)) {
			unset($this->cache[$field]);
		}
		
		if (!($value instanceof RelationshipContent)) {
			$this->record->set($field, $value);
			return $this;
		}
		
		/**
		 * If the data can be cached, we cache it. This prevents database roundtrips.
		 * The only data being cached is relationship related.
		 */
		$this->cache[$field] = $value;
		
		if ($value->isSingle()) {
			$relationship = $this->model->getRelationShips()[$field]->newInstance();
			$this->record->set($relationship->localField()->getName(), $value->getPayload()->first()?->getPrimary());
		}
		
		return $this;
	}
	
	/**
	 * Lazy load the data for a field.
	 */
	public function lazy(string $field) : RelationshipContent
	{
		assert($this->model->getRelationShips()->has($field));
		
		
		$relationship = $this->model->getRelationShips()[$field]->newInstance();
		
		if ($this->cache->has($field)) {
			return $this->cache[$field];
		}
		
		/**
		 * Request the method to find the data we need for the relationship to be resolved
		 * 
		 * @todo We need to manipulate the way an active record sleeps and wakes so it can
		 * "pause" it's database connection properly and resume it wwhen needed.
		 */
		return $relationship->resolve($this);
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

		$schema = $this->connection->getSchema();
		$tablename = $this->model->getTableName();
		
		assert($schema->hasLayoutByName($tablename));
		
		/**
		 * @throws void
		 */
		$table  = $schema->getLayoutByName($tablename);
		
		$_primary = $table->getPrimaryKey();
		assert($_primary instanceof IndexInterface);
		
		$primary = $_primary->getFields()->first();
		
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
				$table,
				$this->record,
				$options
			);
			$fn = function (RecordBeforeInsertEvent $event) {
				$record = $event->getRecord();
				#The insert function is in this closure, which allows the event to cancel storing the data
				$this->getConnection()->insert($event->getLayout(), $record);
			};
		}
		else {
			#Tell the table that the record is being stored
			$event = new RecordBeforeUpdateEvent(
				$this->getConnection(),
				$table,
				$this->record,
				$options
			);
			$fn = function (RecordBeforeUpdateEvent $event) {
				$record = $event->getRecord();
				#The insert function is in this closure, which allows the event to cancel storing the data
				$this->getConnection()->update($event->getLayout(), $record);
			};
		}
		
		$table->events()->dispatch($event, $fn);
	}
	
	/**
	 * Returns the raw data that the database returned for the record.
	 * 
	 * @return Record
	 */
	public function getUnderlyingRecord() : Record
	{
		return $this->record;
	}
	
	/**
	 * A reflection of the model that the active record is holding the data for.
	 * 
	 * @return ReflectionModel<T>
	 */
	public function getModel() : ReflectionModel
	{
		return $this->model;
	}
	
	/**
	 * Returns the connection to the database. The active record can actually use the
	 * database to fetch additional data or write he modified data.
	 * 
	 * @return ConnectionInterface
	 */
	public function getConnection() : ConnectionInterface
	{
		return $this->connection;
	}
}
