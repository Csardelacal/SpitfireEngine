<?php namespace spitfire\storage\database\migration\relational;

use Closure;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor as GenericTableMigrationExecutor;
use spitfire\storage\database\Layout;
use spitfire\storage\database\migration\TagManagerInterface;
use spitfire\storage\database\query\ResultInterface;
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
 * The schema migration executor allows migrations to modify the schema of the
 * database without requiring the user to actually write or maintain any SQL code.
 *
 */
class SchemaMigrationExecutor implements SchemaMigrationExecutorInterface
{
	
	/**
	 *
	 * @var TagManagerInterface|null
	 */
	private $tags;
	
	/**
	 *
	 * @var Connection
	 */
	private $connection;
	
	/**
	 *
	 * @var Adapter
	 */
	private $adapter;
	
	/**
	 *
	 * @var Schema
	 */
	private $schema;
	
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
		$this->adapter = $connection->getAdapter();
		$this->schema = $connection->getSchema();
	}
	
	public function add(string $name, Closure $fn): SchemaMigrationExecutorInterface
	{
		/**
		 * Create a layout and a generic migration executor so we can apply the migration
		 * to the table in a nested way before committing it to the DBMS.
		 */
		$table = new Layout($name);
		$migrator = new GenericTableMigrationExecutor($table);
		
		/**
		 * Apply the migration on the virtual table. This step is necessary since we can't
		 * create a columnless table in the DBMS and populate it later.
		 */
		$fn($migrator);
		
		/**
		 * Create the table according to the MySQL spec.
		 */
		$grammar = $this->adapter->getSchemaGrammar();
		$this->adapter->getDriver()->write($grammar->createTable($table));
		
		return $this;
	}
	
	/**
	 * Renames a table within the current schema. Please note that Spitfire doesn't support moving
	 * tables between schemas.
	 *
	 * @param string $from
	 * @param string $to
	 * @return SchemaMigrationExecutorInterface
	 */
	public function rename(string $from, string $to): SchemaMigrationExecutorInterface
	{
		$grammar = $this->adapter->getSchemaGrammar();
		$this->adapter->getDriver()->write($grammar->renameTable($from, $to));
		
		return $this;
	}
	
	/**
	 * Drops the table from the database. Please note that this operation is not reversible, deleting
	 * a table will lead to all the data in it being deleted.
	 *
	 * @param string $name
	 * @return SchemaMigrationExecutorInterface
	 */
	public function drop(string $name): SchemaMigrationExecutorInterface
	{
		$grammar = $this->adapter->getSchemaGrammar();
		$this->adapter->getDriver()->write($grammar->dropTable($name));
		
		return $this;
	}
	
	/**
	 * This method allows the application to perform operations on a table within the schema. By
	 * providing this namespaced object we reduce the complexity of the migration executors and
	 * make the code to write table changes more readable and less verbose.
	 *
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function table(string $name): TableMigrationExecutorInterface
	{
		return new TableMigrationExecutor($this->adapter, $this->schema->getLayoutByName($name));
	}
	
	/**
	 * Executes an SQL statement on the server. This allows for granular control and server specific
	 * syntax. But it requires your application to implement the specific code for each driver like
	 * this
	 *
	 * ```
	 * if ($migrator instanceof \spitfire\database\drivers\mysqlpdo\SchemaMigrationExecutor) {
	 *   $migrator->execute($customSQL);
	 * }
	 * ```
	 *
	 * This method should be avoided whenever possible.
	 *
	 * @param string $sql
	 * @return SchemaMigrationExecutorInterface
	 */
	public function execute(string $sql): SchemaMigrationExecutorInterface
	{
		$this->adapter->getDriver()->write($sql);
		return $this;
	}
	
	public function has(string $name): bool
	{
		assert($this->schema->getName() !== null);
		
		$grammar = $this->adapter->getSchemaGrammar();
		$stmt = $this->adapter->getDriver()->read($grammar->hasTable($this->schema->getName(), $name));
		
		assert($stmt instanceof ResultInterface);
		return ($stmt->fetchOne()) > 0;
	}
	
	public function tags(): TagManagerInterface
	{
		if (!$this->tags) {
			$this->tags = new TagManager($this->connection);
		}
		
		return $this->tags;
	}
}
