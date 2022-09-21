<?php namespace spitfire\storage\database\migration\group;

use PDO;
use spitfire\collection\Collection;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;
use spitfire\storage\database\Field;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\grammar\mysql\MySQLTableGrammar;
use spitfire\storage\database\Index;
use spitfire\storage\database\IndexInterface;
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
 */
class TableMigrationExecutor implements TableMigrationExecutorInterface
{
	
	
	/**
	 * The schema will contain all the migrated tables and data. Please note that
	 * since this is a reference, the data is being written to the reference directly.
	 *
	 * @var Collection<TableMigrationExecutorInterface>
	 */
	private $migrators;
	
	/**
	 *
	 * @param Collection<TableMigrationExecutorInterface> $migrators
	 */
	public function __construct(Collection $migrators)
	{
		assert($migrators->containsOnly(TableMigrationExecutorInterface::class));
		assert(!$migrators->isEmpty());
		$this->migrators = $migrators;
	}
	
	/**
	 * This method adds an incrementing field to the database. This automatically selects
	 * the datatype
	 *
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function increments(string $name): TableMigrationExecutorInterface
	{
		
		foreach ($this->migrators as $migrator) {
			$migrator->increments($name);
		}
		
		return $this;
	}
	
	/**
	 * This method adds an incrementing field to the database. This automatically selects
	 * the datatype
	 *
	 * @param string $name
	 * @param bool $nullable
	 * @return TableMigrationExecutorInterface
	 */
	public function int(string $name, bool $unsigned, bool $nullable = true): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->int($name, $unsigned, $nullable);
		}
		
		return $this;
	}
	
	/**
	 * This method adds an long integer field to the database.
	 *
	 * @param string $name
	 * @param bool $nullable
	 * @return TableMigrationExecutorInterface
	 */
	public function long(string $name, bool $unsigned, bool $nullable = true): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->long($name, $unsigned, $nullable);
		}
		
		return $this;
	}
	
	/**
	 * Adds a string field to the database. Please note that string fields require the length
	 * parameter. For unlimited length please refer to the text() method.
	 *
	 * @param string $name
	 * @param int $length
	 * @param bool $nullable
	 * @return TableMigrationExecutorInterface
	 */
	public function string(string $name, int $length, bool $nullable = true): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->string($name, $length, $nullable);
		}
		
		return $this;
	}
	
	/**
	 * The text method allows the server to add a column to the database that takes virtually
	 * unlimited amounts of text. Please note that it's still a very bad idea to let users
	 * add unlimited length data to the database.
	 *
	 * @param string $name
	 * @param bool $nullable
	 * @return TableMigrationExecutorInterface
	 */
	public function text(string $name, bool $nullable = true): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->text($name, $nullable);
		}
		
		return $this;
	}
	
	/**
	 * Add an enum field. Enums can hold a set of predefined strings, this is commonly used to
	 * hold state or similar applications.
	 *
	 * @param string $name
	 * @param bool $nullable
	 * @param string[] $options
	 */
	public function enum(string $name, array $options, bool $nullable = true): TableMigrationExecutorInterface
	{
		
		foreach ($this->migrators as $migrator) {
			$migrator->enum($name, $options);
		}
		
		return $this;
	}
	
	/**
	 * Indexes accelerate SELECT queries. Please note that Spitfire does currently not support indexing
	 * of text and blob datatypes.
	 *
	 * @param string $name
	 * @param string[] $fields
	 * @return TableMigrationExecutorInterface
	 */
	public function index(string $name, array $fields): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->index($name, $fields);
		}
		
		return $this;
	}
	
	/**
	 * A foreign key ensures data integrity with another table. This prevents data from being orphaned when
	 * creating, updating or deleting data on the server.
	 *
	 * The referenced layout must have a primary key for the method to work.
	 *
	 * @param string $name
	 * @param TableMigrationExecutorInterface $layout
	 * @return TableMigrationExecutorInterface
	 */
	public function foreign(string $name, TableMigrationExecutorInterface $layout): TableMigrationExecutorInterface
	{
		
		foreach ($this->migrators as $migrator) {
			$migrator->foreign($name, $layout);
		}
		
		return $this;
	}
	
	/**
	 * Indexes accelerate SELECT queries. Please note that Spitfire does currently not support indexing
	 * of text and blob datatypes. UNIQUE indexes also enforce that there may not be any duplicate entries
	 * in the table when adding / updating data.
	 *
	 * @param string $name
	 * @param string[] $fields
	 * @return TableMigrationExecutorInterface
	 */
	public function unique(string $name, array $fields): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->unique($name, $fields);
		}
		
		return $this;
	}
	
	/**
	 * Adds a primary index to the table, using the provided field as
	 *
	 * @param string $field
	 * @return TableMigrationExecutorInterface
	 */
	public function primary(string $field): TableMigrationExecutorInterface
	{
		
		foreach ($this->migrators as $migrator) {
			$migrator->primary($field);
		}
		
		return $this;
	}
	
	/**
	 * The id field provides a consistent way of handling a primary key, by making it
	 * the autoincrement key with the name `_id`
	 *
	 * @return TableMigrationExecutorInterface
	 */
	public function id(): TableMigrationExecutorInterface
	{
		return $this->increments('_id');
	}
	
	/**
	 * Adds the necessary timestamps to maintain a record  of when the record was added
	 * and/or edited.
	 *
	 * @return TableMigrationExecutorInterface
	 */
	public function timestamps(): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->timestamps();
		}
		
		return $this;
	}
	
	/**
	 * Adds a soft delete to the queries. Soft deletes allow the application to
	 * mark records for deletion, but not actually delete them but mask them away
	 * from queries.
	 *
	 * Please note that you should include your removed field in indexes or partition
	 * your table by it so that performance stays strong.
	 *
	 * @return TableMigrationExecutorInterface
	 */
	public function softDelete(): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->softDelete();
		}
		
		return $this;
	}
	
	/**
	 * Drops a column from the DBMS. This destroys the data in that column in
	 * an unrecoverable manner. Please make sure to perform backups of your
	 * database before running this operation.
	 *
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function drop(string $name): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->drop($name);
		}
		
		return $this;
	}
	
	public function dropIndex(string $name): TableMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->dropIndex($name);
		}
		
		return $this;
	}
	
	/**
	 * Get the layout the driver is working with.
	 *
	 * @return LayoutInterface
	 */
	public function layout(): LayoutInterface
	{
		$first = $this->migrators->first();
		assert($first instanceof TableMigrationExecutorInterface);
		return $first->layout();
	}
}
