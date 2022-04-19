<?php namespace spitfire\storage\database\drivers\internal;

use BadMethodCallException;
use Closure;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;
use spitfire\storage\database\Layout;
use spitfire\storage\database\migration\TagManager;
use spitfire\storage\database\migration\TagManagerInterface;
use spitfire\storage\database\Schema;

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
 * The internal schema migrator makes it easy for an application to maintain
 * a schema that will allow the Models on top of it to perform validation and
 * similar tasks.
 */
class SchemaMigrationExecutor implements SchemaMigrationExecutorInterface
{
	
	/**
	 * The schema will contain all the migrated tables and data. Please note that
	 * since this is a reference, the data is being written to the reference directly.
	 *
	 * @var Schema
	 */
	private $schema;
	
	public function __construct(Schema $schema)
	{
		$this->schema = $schema;
	}
	
	/**
	 * Add the table to the schema. Before writing it to the schema, the application may
	 * alter the table.
	 *
	 * @param string $name
	 * @param Closure $fn
	 * @return SchemaMigrationExecutorInterface
	 */
	public function add(string $name, Closure $fn): SchemaMigrationExecutorInterface
	{
		$table = new Layout($name);
		$fn(new TableMigrationExecutor($table));
		
		$this->schema->putLayout($table);
		
		return $this;
	}
	
	/**
	 * Rename the table on the schema.
	 *
	 * @param string $from
	 * @param string $to
	 * @return SchemaMigrationExecutorInterface
	 */
	public function rename(string $from, string $to): SchemaMigrationExecutorInterface
	{
		$table = $this->schema->getLayoutByName($from);
		$this->schema->removeLayout($table)->putLayout($table->withTableName($to));
		
		return $this;
	}
	
	/**
	 * Removes the layout from the schema.
	 *
	 * @param string $name
	 * @return SchemaMigrationExecutorInterface
	 */
	public function drop(string $name): SchemaMigrationExecutorInterface
	{
		$this->schema->removeLayout($this->schema->getLayoutByName($name));
		return $this;
	}
	
	/**
	 * Allows the application to descend into migrations pertaining to the table.
	 *
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function table(string $name): TableMigrationExecutorInterface
	{
		return new TableMigrationExecutor($this->schema->getLayoutByName($name));
	}
	
	public function has(string $name): bool
	{
		return $this->schema->hasLayoutByName($name);
	}
	
	/**
	 * The internal schema migrator cannot execute any SQL code. Invoking this method
	 * will lead to nothing happening.
	 *
	 * @param string $sql
	 * @return SchemaMigrationExecutorInterface
	 */
	public function execute(string $sql): SchemaMigrationExecutorInterface
	{
		return $this;
	}
	
	public function tags():? TagManagerInterface
	{
		return null;
	}
}
