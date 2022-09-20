<?php namespace spitfire\storage\database\drivers;

use Closure;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\Field;
use spitfire\storage\database\Layout;
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
 * @todo Properly document
 */
interface TableMigrationExecutorInterface
{
	
	function increments(string $name) : TableMigrationExecutorInterface;
	
	function int(string $name, bool $unsigned, bool $nullable = true) : TableMigrationExecutorInterface;
	
	function long(string $name, bool $unsigned, bool $nullable = true) : TableMigrationExecutorInterface;
	
	function string(string $name, int $length, bool $nullable = true) : TableMigrationExecutorInterface;
	
	function text(string $name, bool $nullable = true) : TableMigrationExecutorInterface;
	
	/**
	 * Adds an enum field to the table.
	 * 
	 * @param string $name
	 * @param string[] $options
	 * @throws ApplicationException
	 */
	function enum(string $name, array $options, bool $nullable = true) : TableMigrationExecutorInterface;
	
	/**
	 * Adds an index to the fields.
	 * 
	 * @param string $name
	 * @param string[] $fields
	 * @throws ApplicationException
	 */
	function index(string $name, array $fields) : TableMigrationExecutorInterface;
	
	function foreign(string $name, TableMigrationExecutorInterface $layout) : TableMigrationExecutorInterface;
	
	/**
	 * Adds a unique index to the fields.
	 * 
	 * @param string $name
	 * @param string[] $fields
	 * @throws ApplicationException
	 */
	function unique(string $name, array $fields) : TableMigrationExecutorInterface;
	
	function primary(string $name, string $field) : TableMigrationExecutorInterface;
	
	function id() : TableMigrationExecutorInterface;
	
	function timestamps() : TableMigrationExecutorInterface;
	
	function softDelete() : TableMigrationExecutorInterface;
	
	function drop(string $name) : TableMigrationExecutorInterface;
	
	function dropIndex(string $name) : TableMigrationExecutorInterface;
	
	/**
	 * Returns the underlying layout. This contains the current state of the schema.
	 * 
	 * @return LayoutInterface
	 */
	function layout() : LayoutInterface;
}
