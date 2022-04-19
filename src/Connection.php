<?php namespace spitfire\storage\database;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\drivers\internal\SchemaMigrationExecutor;

/**
 * A connection combines a database driver and a schema, allowing the application to
 * maintain a context for the schema it's working on.
 */
class Connection
{
	
	/**
	 *
	 * @var DriverInterface
	 */
	private $driver;
	
	/**
	 *
	 * @var Schema
	 */
	private $schema;
	
	/**
	 *
	 */
	public function __construct(Schema $schema, DriverInterface $driver)
	{
		$this->driver = $driver;
		$this->schema = $schema;
	}
	
	/**
	 * Returns the schema used to model this connection. This provides information about the state
	 * of the database. Models use this to determine which data can be written to the db.
	 *
	 * @return Schema
	 */
	public function getSchema() : Schema
	{
		return $this->schema;
	}
	
	/**
	 * Returns the driver used to manage this connection.
	 *
	 * @return DriverInterface
	 */
	public function getDriver() : DriverInterface
	{
		return $this->driver;
	}
	
	/**
	 * Executes a migration operation on the database. This allows you to create,
	 * upgrade or downgrade database schemas.
	 *
	 * @param MigrationOperationInterface $migration
	 * @return bool True, if the migration has been applied
	 */
	public function contains(MigrationOperationInterface $migration): bool
	{
		$tags = $this->driver->getMigrationExecutor($this->schema)->tags()->listTags();
		
		return !!array_search(
			'migration:' . $migration->identifier(),
			$tags,
			true
		);
	}
	
	/**
	 * Executes a migration operation on the database. This allows you to create,
	 * upgrade or downgrade database schemas.
	 *
	 * @param MigrationOperationInterface $migration
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function apply(MigrationOperationInterface $migration): void
	{
		$migrators = [
			$this->driver->getMigrationExecutor($this->schema),
			new SchemaMigrationExecutor($this->schema)
		];
		
		foreach ($migrators as $migrator) {
			$migration->up($migrator);
			$migrator->tags()? $migrator->tags()->tag('migration:' . $migration->identifier()) : null;
		}
	}
	
	/**
	 * Rolls a migration back. Undoing it's changes to the schema.
	 *
	 * @param MigrationOperationInterface $migration
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function rollback(MigrationOperationInterface $migration): void
	{
		$migrators = [
			$this->driver->getMigrationExecutor($this->schema),
			new SchemaMigrationExecutor($this->schema)
		];
		
		foreach ($migrators as $migrator) {
			$migration->down($migrator);
			$migrator->tags()? $migrator->tags()->untag('migration:' . $migration->identifier()) : null;
		}
	}
}
