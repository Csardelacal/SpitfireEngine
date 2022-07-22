<?php namespace spitfire\storage\database\migration\relational;

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
	 * The database connection used to apply the migrations.
	 *
	 * @var Adapter
	 */
	private $adapter;
	
	/**
	 * The name of the table receiving the changes.
	 *
	 * @var LayoutInterface
	 */
	private $table;
	
	/**
	 *
	 * @param Adapter $adapter
	 * @param LayoutInterface $table
	 */
	public function __construct(Adapter $adapter, LayoutInterface $table)
	{
		$this->adapter = $adapter;
		$this->table = $table;
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
		$grammar = new MySQLTableGrammar();
		
		/**
		 * An autoincrement field must always be paired with a primary index.
		 *
		 * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table-examples.html
		 */
		$field = new Field($name, 'long:unsigned', false, true);
		$index = new Index('_primary', new Collection([$field]), true, true);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addColumn($field), $grammar->addIndex($index)]
		));
		
		return $this;
	}
	
	/**
	 * This method adds an incrementing field to the database. This automatically selects
	 * the datatype
	 *
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function int(string $name, bool $unsigned): TableMigrationExecutorInterface
	{
		$grammar = new MySQLTableGrammar();
		$field = new Field($name, $unsigned? 'int' : 'int:unsigned', true, false);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addColumn($field)]
		));
		
		return $this;
	}
	
	/**
	 * Adds a string field to the database. Please note that string fields require the length
	 * parameter. For unlimited length please refer to the text() method.
	 *
	 * @param string $name
	 * @param int $length
	 * @return TableMigrationExecutorInterface
	 */
	public function string(string $name, int $length): TableMigrationExecutorInterface
	{
		/**
		 * Obviously, the length needs to be a positive number. Otherwise
		 * this would be weird.
		 */
		assert($length > 0);
		
		$grammar = new MySQLTableGrammar();
		$field = new Field($name, 'string:' . $length, true, false);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addColumn($field)]
		));
		
		return $this;
	}
	
	/**
	 * The text method allows the server to add a column to the database that takes virtually
	 * unlimited amounts of text. Please note that it's still a very bad idea to let users
	 * add unlimited length data to the database.
	 *
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function text(string $name): TableMigrationExecutorInterface
	{
		$grammar = new MySQLTableGrammar();
		$field = new Field($name, 'text', true, false);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addColumn($field)]
		));
		
		return $this;
	}
	
	/**
	 * Add an enum field. Enums can hold a set of predefined strings, this is commonly used to
	 * hold state or similar applications.
	 *
	 * @param string $name
	 * @param string[] $options
	 */
	public function enum(string $name, array $options): TableMigrationExecutorInterface
	{
		/**
		 * Verify that none of the options contains a comma. This ensures that the developer
		 * is not causing any inconsistent behavior. This code is only executed during testing
		 * and is generally not expected to run in production.
		 */
		assert((new Collection($options))->filter(function (string $e) : bool {
			return strstr($e, ',') !== false;
		})->isEmpty());
		
		/**
		 * Set up the grammar and add the field to the DBMS.
		 */
		$grammar = new MySQLTableGrammar();
		$field = new Field($name, 'enum:' . implode(',', $options), true, false);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addColumn($field)]
		));
		
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
		$grammar = new MySQLTableGrammar();
		$_fields = (new Collection($fields))->each(function (string $name) {
			return $this->table->getField($name);
		});
		$index = new Index($name, $_fields, false, false);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addIndex($index)]
		));
		
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
		/**
		 * If the referenced layout does not have a primary key the code cannot
		 * continue.
		 */
		assert($layout->layout()->getPrimaryKey() !== null);
		
		$grammar = new MySQLTableGrammar();
		
		/**
		 * Create a field to host the data for the referenced field. Rename the field
		 * to prefix it with the name we want to assign to this field.
		 */
		$reference = $layout->layout()->getPrimaryKey()->getFields()[0];
		$field = $this->table->putField($name, $reference->getType(), $reference->isNullable(), false);
		
		$index = new ForeignKey(
			sprintf('fk_%s', $name),
			$field,
			($layout)->layout()->getTableReference()->getOutput($layout->layout()->getPrimaryKey()->getName())
		);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[
				$grammar->addColumn($field),
				$grammar->addIndex($index)
			]
		));
		
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
		$grammar = new MySQLTableGrammar();
		$index = new Index($name, (new Collection($fields))->each(function ($e) {
			return $this->table->getField($e);
		}), true, false);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addIndex($index)]
		));
		
		return $this;
	}
	
	/**
	 * Adds a primary index to the table, using the provided field as
	 *
	 * @param string $name
	 * @param string $field
	 * @return TableMigrationExecutorInterface
	 */
	public function primary(string $name, string $field): TableMigrationExecutorInterface
	{
		
		$grammar = new MySQLTableGrammar();
		
		/**
		 * An autoincrement field must always be paired with a primary index.
		 *
		 * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table-examples.html
		 */
		$_field = $this->table->getField($name);
		$index = new Index('_primary', new Collection([$_field]), true, true);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addIndex($index)]
		));
		
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
		$grammar = new MySQLTableGrammar();
		
		/**
		 * The created and updated fields contain information to indicate
		 * when the record was edited/added. This is often used to perform
		 * housekeeping operations on the system.
		 */
		$created = new Field('created', 'int:unsigned', false, false);
		$updated = new Field('updated', 'int:unsigned', false, false);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addColumn($created), $grammar->addColumn($updated)]
		));
		
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
		$grammar = new MySQLTableGrammar();
		$removed = new Field('removed', 'int:unsigned', false, false);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->addColumn($removed)]
		));
		
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
		$field = $this->table->getField($name);
		$grammar = new MySQLTableGrammar();
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->dropColumn($field)]
		));
		
		return $this;
	}
	
	public function dropIndex(string $name): TableMigrationExecutorInterface
	{
		$grammar = new MySQLTableGrammar();
		
		/**
		 * Find the index that we
		 */
		$index = $this->table->getIndexes()->filter(function (IndexInterface $index) use ($name) {
			return $index->getName() === $name;
		})->first();
		
		assert($index !== null);
		
		$this->adapter->getDriver()->write($grammar->alterTable(
			$this->table->getTableName(),
			[$grammar->dropIndex($index)]
		));
		
		return $this;
	}
	
	/**
	 * Get the layout the driver is working with.
	 *
	 * @return LayoutInterface
	 */
	public function layout(): LayoutInterface
	{
		return $this->table;
	}
}
