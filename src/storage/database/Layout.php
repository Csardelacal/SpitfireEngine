<?php namespace spitfire\storage\database;

/*
 *
 * Copyright (C) 2021-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
use spitfire\event\EventTarget;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\identifiers\TableIdentifier;
use spitfire\storage\database\identifiers\TableIdentifierInterface;


/**
 * The layout is basically a list of columns and indexes that makes up the schema
 * of a relation in a relational database. Spitfire constructs these objects automatically
 * from models so it can interact with the database consistently.
 *
 * Please note that, these are similar to model fields, but not 100% compatible, they
 * are not interchangeable.
 */
class Layout implements LayoutInterface
{
	
	const PRIMARY_KEY = '_PRIMARY';
	
	/**
	 * The name of the table this layout represents in the DBMS. This table name
	 * must be valid in your DBMS, if this is not the case, the application will
	 * fail without proper notice.
	 *
	 * Therefore it's recommended to generate table names without slashes, or any
	 * non ASCII characters.
	 *
	 * @var string
	 */
	private $tablename;
	
	/**
	 * The fields or columns this table contains. Each column can hold
	 * a certain data type, we ensure that the data we write / read from
	 * the DBMS has the appropriate type.
	 *
	 * @var Collection<Field>
	 */
	private $fields;
	
	/**
	 * The indexes in this relation. This information is used when constructing
	 * tables only, and bears no relevance when querying the database.
	 *
	 * @var Collection<IndexInterface>
	 */
	private $indexes;
	
	/**
	 *
	 * @var EventTarget
	 */
	private $events;
	
	/**
	 *
	 * @param string $tablename
	 */
	public function __construct(string $tablename)
	{
		$this->tablename = $tablename;
		$this->fields = new TypedCollection(Field::class);
		$this->indexes = new TypedCollection(IndexInterface::class);
		$this->events = new EventTarget();
	}
	
	/**
	 * Returns the name the DBMS should use to name this table. The implementing
	 * class should respect user configuration including db_table_prefix
	 *
	 * @return string
	 */
	public function getTableName() : string
	{
		return $this->tablename;
	}
	
	/**
	 * Creates a copy of the layout with the appropriate table name.
	 *
	 * @param string $name
	 * @return Layout
	 */
	public function withTableName($name) : Layout
	{
		$copy = clone $this;
		$copy->tablename = $name;
		return $copy;
	}
	
	/**
	 * Gets a certain field from the layout.
	 *
	 * @throws ApplicationException
	 * @param string $name
	 * @return Field
	 */
	public function getField(string $name) : Field
	{
		if (!$this->fields->has($name)) {
			throw new ApplicationException(sprintf('Field %s is not available in %s', $name, $this->tablename));
		}
		
		return $this->fields[$name];
	}
	
	/**
	 * Sets a certain field to a certain value.
	 *
	 * @deprecated Please avoid this method, since it's able to introduce buggy behaviors.
	 * @param string $name
	 * @param Field $field
	 * @return Layout
	 */
	public function setField(string $name, Field $field) : Layout
	{
		$this->fields[$name] = $field;
		return $this;
	}
	
	/**
	 * Adds a field to the layout. The database will return this so you can easily
	 * manipulate fields for indexes or similar purposes.
	 *
	 * @param string $name
	 * @param string $type
	 * @param bool $nullable
	 * @param bool $autoIncrement
	 *
	 * @return Field
	 */
	public function putField(string $name, string $type, bool $nullable = true, bool $autoIncrement = false) : Field
	{
		$field = new Field($name, $type, $nullable, $autoIncrement);
		$this->fields[$name] = $field;
		
		return $field;
	}
	
	/**
	 * Removes the field from the layout.
	 *
	 * @param string $name
	 * @return Layout
	 */
	public function unsetField(string $name) : Layout
	{
		unset($this->fields[$name]);
		return $this;
	}
	
	/**
	 * Adds the fields we received from a collection, this is specially useful since
	 * logical fields will return collections when generating physical fields.
	 *
	 * @param Collection<Field> $fields
	 * @return Layout
	 */
	public function addFields(Collection $fields) : Layout
	{
		assert($fields->containsOnly(Field::class));
		$fields->each(function ($e) {
			$this->fields[$e->getName()] = $e;
		});
		return $this;
	}
	
	/**
	 * Returns true if the layout contains a certain field.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasField(string $name) : bool
	{
		return $this->fields->has($name);
	}
	
	/**
	 * Returns true if the layout contains a certain field.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasIndex(string $name) : bool
	{
		return $this->indexes->filter(fn(IndexInterface $e) => $e->getName() === $name)->first() !== null;
	}
	
	/**
	 *
	 * @return Collection<Field> The columns in this database table
	 */
	public function getFields() : Collection
	{
		return $this->fields;
	}
	
	/**
	 * Add an index spanning the given fields. Please note that the index will receive
	 * a random name to ensure it's unique.
	 *
	 * @param string $name
	 * @param Field $fields
	 * @return Index
	 */
	public function index($name, ...$fields) : Index
	{
		$index = new Index($name, Collection::fromArray($fields));
		$this->indexes->push($index);
		
		return $index;
	}
	
	/**
	 * Add a unique index spanning the given fields. Please note that the index will receive
	 * a random name to ensure it's unique.
	 *
	 * @param string $name
	 * @param Field $fields
	 * @return Index
	 */
	public function unique(string $name, ...$fields) : Index
	{
		$index = new Index($name, Collection::fromArray($fields), true);
		$this->indexes->push($index);
		
		return $index;
	}
	
	/**
	 * Set the primary index to a certain field.
	 *
	 * @param Field $field
	 * @return Index
	 */
	public function primary(Field $field) : Index
	{
		$index = new Index('_PRIMARY', new Collection($field), true, true);
		$this->indexes->push($index);
		
		return $index;
	}
	
	/**
	 * Puts an index onto the table. Please note that the layout must contain the
	 * fields that the index is trying to index.
	 *
	 * @param IndexInterface $index
	 */
	public function putIndex(IndexInterface $index) : void
	{
		/**
		 * This assertion ensures that the layout contains the fields indexed by the
		 * key. Obviously this needs only be checked during development but should
		 * be safe to be assumed to be the case in production.
		 */
		assert($index->getFields()->reduce(function (bool $c, Field $e) {
			return $c && $this->hasField($e->getName());
		}, true) === true);
		
		$this->indexes->push($index);
	}
	
	/**
	 * This method needs to get the lost of indexes from the logical Schema and
	 * convert them to physical indexes for the DBMS to manage.
	 *
	 * @return Collection<IndexInterface> The indexes in this layout
	 */
	public function getIndexes() : Collection
	{
		return $this->indexes;
	}
	
	/**
	 * Returns true if the layout contains a certain field.
	 *
	 * @param string $name
	 * @return IndexInterface
	 */
	public function getIndex(string $name) : IndexInterface
	{
		$res = $this->indexes->filter(fn(IndexInterface $e) => $e->getName() === $name)->first();
		assert($res !== null);
		return $res;
	}
	
	/**
	 * Removes an index by it's name from the database. Note that the default name
	 * for primary indexes is _PRIMARY
	 *
	 * @param string $name
	 * @return LayoutInterface
	 */
	public function unsetIndex(string $name) : LayoutInterface
	{
		$this->indexes = $this->indexes->filter(function (IndexInterface $e) use ($name) {
			return $e->getName() !== $name;
		});
		
		return $this;
	}
	
	/**
	 * Get's the table's primary key. This will always return an array
	 * containing the fields the Primary Key contains.
	 *
	 * @return IndexInterface|null
	 */
	public function getPrimaryKey() :? IndexInterface
	{
		$indexes = $this->indexes;
		return $indexes->filter(function (IndexInterface $i) {
			return $i->isPrimary();
		})->first();
	}
	
	/**
	 * Get the auto increment field, or null if there is none for this table.
	 *
	 * @return Field|null
	 */
	public function getAutoIncrement() :? Field
	{
		$fields = $this->fields;
		return $fields->filter(function (Field $i) {
			return $i->isAutoIncrement();
		})->first();
	}
	
	public function getTableReference() : TableIdentifierInterface
	{
		return new TableIdentifier([$this->getTableName()], $this->fields->each(function (Field $e) {
			return $e->getName();
		}));
	}
	
	/**
	 * This provides access to the table's event dispatching. This is required for
	 * operations that update the record before it's written to the database, or prevent
	 * deletions when soft deletes are on.
	 *
	 * @return EventTarget
	 */
	public function events() : EventTarget
	{
		return $this->events;
	}
	
	public function __clone()
	{
		$this->indexes = clone $this->indexes;
		$this->fields = clone $this->fields;
		$this->events = clone $this->events;
	}
}
