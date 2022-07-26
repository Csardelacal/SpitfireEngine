<?php namespace spitfire\storage\database;

use spitfire\collection\Collection;
use spitfire\event\EventDispatch;
use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\NotFoundException;
use spitfire\storage\database\identifiers\TableIdentifierInterface;

/*
 * The MIT License
 *
 * Copyright 2017 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The layout is basically a list of columns + indexes that makes up the schema
 * of a relation in a relational database.
 *
 * A driver can implement this interface to provide common operations on it's
 * tables for spitfire to run. This should generally be avoided, using a generic
 * layout class for most operations.
 *
 * Since layouts are generally considered immutable (it makes little to no sense
 * to change the layout during runtime), this interface provides no utilities to
 * edit the layout.
 */
interface LayoutInterface
{
	
	/**
	 * Returns the name the DBMS should use to name this table. The implementing
	 * class should respect user configuration including db_table_prefix
	 *
	 * @return string
	 */
	public function getTableName() : string;
	
	/**
	 * Get a single field by it's name. If the field is not existant, the application
	 * should throw an exception.
	 *
	 * @param string $name
	 * @throws NotFoundException
	 * @return Field
	 */
	public function getField(string $name) : Field;
	
	/**
	 * Returns true if the layout contains a certain field.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasField(string $name) : bool;
	
	/**
	 * Get the list of fields in this layout. This allows the database driver to
	 * determine which columns can be used to manage the database.
	 *
	 * @return Collection<Field> The columns in this database table
	 */
	public function getFields();
	
	/**
	 * This method needs to get the list of indexes from the logical Schema and
	 * convert them to physical indexes for the DBMS to manage.
	 *
	 * @return Collection<IndexInterface> The indexes in this layout
	 */
	public function getIndexes();
	
	/**
	 * Adds a field to the layout.
	 *
	 * @return Field
	 */
	public function putField(string $name, string $type, bool $nullable = true, bool $autoIncrement = false) : Field;
	
	/**
	 * Puts an index onto the table. Please note that the layout must contain the
	 * fields that the index is trying to index.
	 *
	 * @param IndexInterface $index
	 */
	public function putIndex(IndexInterface $index) : void;
	
	/**
	 * Add an index spanning the given fields. Please note that the index will receive
	 * a random name to ensure it's unique.
	 *
	 * @param string $name
	 * @param Field[] $fields
	 * @return Index
	 */
	public function index($name, ...$fields) : Index;
	
	/**
	 * Find an index by it's name within the layout.
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function hasIndex(string $name) : bool;
	
	/**
	 * Get an index by it's name within the layout
	 * 
	 * @param string $name
	 * @return IndexInterface The index
	 * @throws ApplicationException
	 */
	public function getIndex(string $name) : IndexInterface;
	
	/**
	 * Set the primary index to a certain field.
	 *
	 * @param Field $field
	 * @return Index
	 */
	public function primary(Field $field) : Index;
	
	/**
	 * Get's the table's primary key. This will always return an array
	 * containing the fields the Primary Key contains.
	 *
	 * @return IndexInterface|null
	 */
	public function getPrimaryKey() :? IndexInterface;
	
	
	/**
	 * Removes the field from the layout.
	 *
	 * @param string $name
	 * @return Layout
	 */
	public function unsetField(string $name) : Layout;
	
	/**
	 * Removes an index by it's name from the database. Note that the default name
	 * for primary indexes is _PRIMARY
	 *
	 * @param string $name
	 * @return LayoutInterface
	 */
	public function unsetIndex(string $name) : LayoutInterface;
	
	/**
	 * Returns a reference to this table. These are used by queries and migrations for generating
	 * SQL and passing along references.
	 *
	 * @return TableIdentifierInterface
	 */
	public function getTableReference() : TableIdentifierInterface;
	
	/**
	 * This provides access to the table's event dispatching. This is required for
	 * operations that update the record before it's written to the database, or prevent
	 * deletions when soft deletes are on.
	 *
	 * @return EventDispatch
	 */
	public function events() : EventDispatch;
}
