<?php namespace spitfire\storage\database\drivers;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\LayoutInterface;

/*
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-1301  USA
 */

/**
 * This executes migrations on the underlying driver, but scoped to a table.
 *
 */
interface TableMigrationExecutorInterface
{
	
	/**
	 * Adds an increments field to the table. Most DBMS will only allow
	 * you to use one such table and it might have to be the primary key
	 * of said table.
	 *
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function increments(string $name) : TableMigrationExecutorInterface;
	
	/**
	 * Adds an integer field to the table.
	 *
	 * @param string $name
	 * @param bool $unsigned Wheether the application can write negative numbers into this field
	 * @param bool $nullable This allows the application to write null values ot this field
	 * @return TableMigrationExecutorInterface
	 */
	public function int(string $name, bool $unsigned, bool $nullable = true) : TableMigrationExecutorInterface;
	
	/**
	 * Adds an  long integer field to the table. These fields have twice the size, and t
	 * therefor can hold significanlty larger numbers
	 *
	 * @param string $name
	 * @param bool $unsigned Wheether the application can write negative numbers into this field
	 * @param bool $nullable This allows the application to write null values ot this field
	 * @return TableMigrationExecutorInterface
	 */
	public function long(string $name, bool $unsigned, bool $nullable = true) : TableMigrationExecutorInterface;
	
	/**
	 * Adds a string field to the table.
	 *
	 * @param string $name
	 * @param int $length The maximum length of the string data being written to this
	 * @param bool $nullable This allows the application to write null values ot this field
	 * @return TableMigrationExecutorInterface
	 */
	public function string(string $name, int $length, bool $nullable = true) : TableMigrationExecutorInterface;
	
	/**
	 * Adds a string field with no size limit to the table.
	 *
	 * @param string $name
	 * @param bool $nullable This allows the application to write null values ot this field
	 * @return TableMigrationExecutorInterface
	 */
	public function text(string $name, bool $nullable = true) : TableMigrationExecutorInterface;
	
	/**
	 * Adds an enum field to the table.
	 *
	 * @param string $name
	 * @param string[] $options
	 * @throws ApplicationException
	 */
	public function enum(string $name, array $options, bool $nullable = true) : TableMigrationExecutorInterface;
	
	/**
	 * Adds an index to the fields.
	 *
	 * @param string $name
	 * @param string[] $fields
	 * @throws ApplicationException
	 */
	public function index(string $name, array $fields) : TableMigrationExecutorInterface;
	
	/**
	 * Adds a foreign key to the table.
	 *
	 * @param string $name
	 * @param TableMigrationExecutorInterface $layout The table to link to
	 */
	public function foreign(string $name, TableMigrationExecutorInterface $layout) : TableMigrationExecutorInterface;
	
	/**
	 * Adds a unique index to the fields.
	 *
	 * @param string $name
	 * @param string[] $fields
	 * @throws ApplicationException
	 */
	public function unique(string $name, array $fields) : TableMigrationExecutorInterface;
	
	/**
	 * Add a primary key to the field provided
	 *
	 * @param string $field
	 * @return self
	 */
	public function primary(string $field) : TableMigrationExecutorInterface;
	
	/**
	 * The id method automatically adds an id column to the table. This column
	 * will be automatically named according to convention (usually _id), made
	 * to increment and make it the table's primary key.
	 *
	 * @return self
	 */
	public function id() : TableMigrationExecutorInterface;
	
	/**
	 * This adds the created and updated columns to the table and introduces a listener to the
	 * schema to update the value when the records are either being created or updated.
	 *
	 * @return self
	 */
	public function timestamps() : TableMigrationExecutorInterface;
	
	/**
	 * This adds a 'removed' column to the database and a mechanism to automatically include a
	 * filter to exclude any deleted results when querying the database.
	 *
	 * @return self
	 */
	public function softDelete() : TableMigrationExecutorInterface;
	
	/**
	 * Drops a column from this table.
	 *
	 * @param string $name The name of the column to be dropped
	 * @return self
	 */
	public function drop(string $name) : TableMigrationExecutorInterface;
	
	/**
	 * Drops an index from the table.
	 *
	 * @param string $name The name of the index to be dropped
	 * @return self
	 */
	public function dropIndex(string $name) : TableMigrationExecutorInterface;
	
	/**
	 * Returns the underlying layout. This contains the current state of the schema.
	 *
	 * @return LayoutInterface
	 */
	public function layout() : LayoutInterface;
}
