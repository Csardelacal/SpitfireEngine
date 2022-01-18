<?php namespace spitfire\storage\database;

use spitfire\collection\Collection;
use spitfire\exceptions\NotFoundException;

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
	function getTableName() : string;
	
	/**
	 * Get a single field by it's name. If the field is not existant, the application
	 * should throw an exception.
	 * 
	 * @param string $name
	 * @throws NotFoundException
	 * @return Field
	 */
	function getField(string $name) : Field;
	
	/**
	 * Get the list of fields in this layout. This allows the database driver to
	 * determine which columns can be used to manage the database.
	 * 
	 * @return Collection<Field> The columns in this database table
	 */
	function getFields();
	
	/**
	 * This method needs to get the list of indexes from the logical Schema and 
	 * convert them to physical indexes for the DBMS to manage.
	 * 
	 * @return Collection<IndexInterface> The indexes in this layout
	 */
	function getIndexes();
	
	/**
	 * Get's the table's primary key. This will always return an array
	 * containing the fields the Primary Key contains.
	 * 
	 * @return IndexInterface|null
	 */
	public function getPrimaryKey() :? IndexInterface;
	
}
