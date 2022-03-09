<?php namespace spitfire\storage\database\drivers;

use Closure;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\migration\TagManagerInterface;

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
 * This class is intended to create an interface for common migration operations
 * to be performed on schemas.
 *
 * @todo Add  an import method that migrates a schema onto an empty one
 * @todo Add empty/reset method that removes all tables from the schema
 */
interface SchemaMigrationExecutorInterface
{
	
	/**
	 * Creates a table according to the specification of the closure. The system must
	 * invoke the closure and pass a TableMigrationExecutorInterface to it so it can
	 * assemble a proper table that can then be applied to the DBMS
	 *
	 * @param string $name
	 * @param Closure $fn
	 * @return SchemaMigrationExecutorInterface
	 */
	public function add(string $name, Closure $fn) : SchemaMigrationExecutorInterface;
	
	/**
	 * Renames the table from one name to another.
	 *
	 * @param string $from
	 * @param string $to
	 * @return SchemaMigrationExecutorInterface
	 */
	public function rename(string $from, string $to) : SchemaMigrationExecutorInterface;
	
	/**
	 * Removes the table from the underlying driver.
	 *
	 * @throws ApplicationException
	 * @param string $name
	 * @return SchemaMigrationExecutorInterface
	 */
	public function drop(string $name) : SchemaMigrationExecutorInterface;
	
	/**
	 * Returns true if the schema has a table with the provided name.
	 *
	 * @param string $name
	 * @return bool
	 */
	public function has(string $name) : bool;
	
	/**
	 * Access the table migration executor for the current database, this allows the application
	 * to perform changes to the tables themselves.
	 *
	 * @throws ApplicationException
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function table(string $name) : TableMigrationExecutorInterface;
	
	/**
	 * Performs raw SQL on the database. Please note that this may cause inconsistent behavior
	 * between schemas and the DBMS, use with caution.
	 *
	 * @throws ApplicationException
	 * @param string $sql
	 * @return SchemaMigrationExecutorInterface
	 */
	public function execute(string $sql) : SchemaMigrationExecutorInterface;
	
	/**
	 * Allows the schema to be tagged. This allows the application to maintain an in-channel
	 * record of the state of the database, the migrations applied, etc.
	 *
	 * @return TagManagerInterface
	 */
	public function tags() : TagManagerInterface;
}
