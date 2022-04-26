<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use BadMethodCallException;
use Closure;
use PDO;
use PDOStatement;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;
use spitfire\storage\database\drivers\internal\TableMigrationExecutor as GenericTableMigrationExecutor;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\Layout;
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
 * The schema migration executor allows migrations to modify the schema of the
 * database without requiring the user to actually write or maintain any SQL code.
 */
class SchemaMigrationExecutor implements SchemaMigrationExecutorInterface
{
	
	/**
	 *
	 * @var PDO
	 */
	private $pdo;
	
	/**
	 *
	 * @var Schema
	 */
	private $schema;
	
	public function __construct(PDO $pdo, Schema $schema)
	{
		$this->pdo = $pdo;
		$this->schema = $schema;
	}
	
	public function add(string $name, Closure $fn): SchemaMigrationExecutorInterface
	{
		/**
		 * Create a layout and a generic migration executor so we can apply the migration
		 * to the table in a nested way before committing it to the DBMS.
		 *
		 * @todo This depends on the generic migration system, since it is required to
		 * perform all the table operations before the table is created. Passing a blank
		 * table will provide a mechanism to create the table
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
		$grammar = new MySQLSchemaGrammar();
		$this->pdo->exec($grammar->createTable($table));
		
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
		$grammar = new MySQLSchemaGrammar();
		$this->pdo->exec($grammar->renameTable($from, $to));
		
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
		$grammar = new MySQLSchemaGrammar();
		$this->pdo->exec($grammar->dropTable($name));
		
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
		return new TableMigrationExecutor($this->pdo, $this->schema->getLayoutByName($name));
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
		$this->pdo->exec($sql);
		return $this;
	}
	
	public function has(string $name): bool
	{
		assert($this->schema->getName() !== null);
		
		$grammar = new MySQLSchemaGrammar();
		$stmt = $this->pdo->query($grammar->hasTable($this->schema->getName(), $name));
		
		assert($stmt instanceof PDOStatement);
		return ($stmt->fetch()[0]) > 0;
	}
	
	public function tags(): TagManagerInterface
	{
		throw new BadMethodCallException('Not yet implemented');
	}
}
