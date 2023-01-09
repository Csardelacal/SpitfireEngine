<?php namespace spitfire\storage\database;

use PDOStatement;
use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\events\RecordBeforeInsertEvent;
use spitfire\storage\database\migration\group\SchemaMigrationExecutor as GroupSchemaMigrationExecutor;
use spitfire\storage\database\migration\relational\SchemaMigrationExecutor as RelationalSchemaMigrationExecutor;
use spitfire\storage\database\migration\schemaState\SchemaMigrationExecutor as SchemaStateSchemaMigrationExecutor;
use spitfire\storage\database\query\ResultInterface;

/**
 * A connection combines a database driver and a schema, allowing the application to
 * maintain a context for the schema it's working on.
 */
class Connection implements ConnectionInterface
{
	
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
	
	/**
	 *
	 * @var SchemaMigrationExecutorInterface|null
	 */
	private $migrator;
	
	/**
	 *
	 */
	public function __construct(Schema $schema, Adapter $adapter)
	{
		$this->adapter = $adapter;
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
	
	public function setSchema(Schema $schema) : Connection
	{
		$this->schema = $schema;
		return $this;
	}
	
	public function getAdapter() : Adapter
	{
		return $this->adapter;
	}
	
	public function getMigrationExecutor() : SchemaMigrationExecutorInterface
	{
		if ($this->migrator === null) {
			/**
			 * 
			 * @var Collection<SchemaMigrationExecutorInterface>
			 */
			$executors = Collection::fromArray([
				new RelationalSchemaMigrationExecutor($this),
				new SchemaStateSchemaMigrationExecutor($this->schema)
			]);
				
			$this->migrator = new GroupSchemaMigrationExecutor($executors);
		}
		
		return $this->migrator;
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
		$manager = $this->getMigrationExecutor()->tags();
		
		$tags = $manager->listTags();
		
		$result = false !== array_search(
			'migration:' . $migration->identifier(),
			$tags,
			true
		);
		
		return $result;
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
		
		$migrator = $this->getMigrationExecutor();
		$migration->up($migrator);
		$migrator->tags()->tag('migration:' . $migration->identifier());
	}
	
	/**
	 * Rolls a migration back. Undoing it's changes to the schema.
	 *
	 * @param MigrationOperationInterface $migration
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function rollback(MigrationOperationInterface $migration): void
	{
		$migrator = $this->getMigrationExecutor();
		$migration->down($migrator);
		$migrator->tags()->untag('migration:' . $migration->identifier());
	}
	
	/**
	 * Query the database for data. The query needs to encapsulate all the data
	 * that is needed for our DBMS to execute the query.
	 *
	 * @param Query $query
	 * @return ResultInterface
	 */
	public function query(Query $query): ResultInterface
	{
		$sql = $this->adapter->getQueryGrammar()->query($query);
		return $this->adapter->getDriver()->read($sql);
	}
	
	public function update(LayoutInterface $layout, Record $record): bool
	{
		$stmt   = $this->adapter->getRecordGrammar()->updateRecord($layout, $record);
		$result = $this->adapter->getDriver()->write($stmt);
		
		/**
		 * Commit that the record has been written to the database. The record will be in sync
		 * with the database.
		 */
		$record->commit();
		
		return $result !== false;
	}
	
	
	public function insert(LayoutInterface $layout, Record $record): bool
	{
		$event  = new RecordBeforeInsertEvent($this, $layout, $record);
		
		$layout->events()->dispatch(
			$event,
			function () {
			}
		);
		
		if ($event->isPrevented()) {
			return true;
		}
		
		$stmt = $this->adapter->getRecordGrammar()->insertRecord($layout, $record);
		$result = $this->adapter->getDriver()->write($stmt);
		
		/**
		 * In the event that the field is automatically incremented, the dbms
		 * will provide us with the value it inserted. This value needs to be
		 * stored to the record.
		 */
		$increment = $layout->getFields()->filter(function (Field $field) {
			return $field->isAutoIncrement();
		})->first();
		
		if ($increment !== null) {
			$id = $this->adapter->getDriver()->lastInsertId();
			$record->set($increment->getName(), $id);
		}
		
		/**
		 * Since the database data is now in sync with the contents of the
		 * record, we can commit the record as containing the same data that
		 * the DBMS does.
		 */
		$record->commit();
		
		return $result !== false;
	}
	
	
	public function delete(LayoutInterface $layout, Record $record): bool
	{
		$stmt = $this->adapter->getRecordGrammar()->deleteRecord($layout, $record);
		$result = $this->adapter->getDriver()->write($stmt);
		
		return $result !== false;
	}
	
	public function has(string $name): bool
	{
		$sql  = $this->adapter->getSchemaGrammar()->hasTable($this->schema->getName()?: '', $name);
		$stmt = $this->adapter->getDriver()->read($sql);
		
		assert($stmt instanceof PDOStatement);
		return ($stmt->fetchColumn()) > 0;
	}
	
	public function __clone()
	{
		$this->migrator = null;
	}
}
