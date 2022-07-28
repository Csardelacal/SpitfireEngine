<?php namespace spitfire\storage\database;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\query\ResultInterface;

/**
 * A connection combines a database driver and a schema, allowing the application to
 * maintain a context for the schema it's working on.
 */
interface ConnectionInterface
{
	
	/**
	 * Returns the schema used to model this connection. This provides information about the state
	 * of the database. Models use this to determine which data can be written to the db.
	 *
	 * @return Schema
	 */
	public function getSchema() : Schema;
	
	public function getAdapter() : Adapter;
	
	public function getMigrationExecutor() : SchemaMigrationExecutorInterface;
	
	/**
	 * Executes a migration operation on the database. This allows you to create,
	 * upgrade or downgrade database schemas.
	 *
	 * @param MigrationOperationInterface $migration
	 * @return bool True, if the migration has been applied
	 */
	public function contains(MigrationOperationInterface $migration): bool;
	
	/**
	 * Executes a migration operation on the database. This allows you to create,
	 * upgrade or downgrade database schemas.
	 *
	 * @param MigrationOperationInterface $migration
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function apply(MigrationOperationInterface $migration): void;
	
	/**
	 * Rolls a migration back. Undoing it's changes to the schema.
	 *
	 * @param MigrationOperationInterface $migration
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function rollback(MigrationOperationInterface $migration): void;
	
	/**
	 * Query the database for data. The query needs to encapsulate all the data
	 * that is needed for our DBMS to execute the query.
	 *
	 * @param Query $query
	 * @return ResultInterface
	 */
	public function query(Query $query): ResultInterface;
	
	public function update(LayoutInterface $layout, Record $record): bool;
	
	public function insert(LayoutInterface $layout, Record $record): bool;
	
	
	public function delete(LayoutInterface $layout, Record $record): bool;
	
	public function has(string $name): bool;
}
