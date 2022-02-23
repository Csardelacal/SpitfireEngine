<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use PDO;
use Psr\Log\LoggerInterface;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\drivers\internal\MockResultSet;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\MigrationOperationInterface;
use spitfire\storage\database\Query;
use spitfire\storage\database\Record;
use spitfire\storage\database\ResultSetInterface;
use spitfire\storage\database\Settings;

/**
 * The NOOP driver will generate SQL for the database, but instead of returning data,
 * it will only generate the SQL and return successful empty states.
 *
 * This can be used for testing and for generating SQL in dry-run environments where the
 * SQL just needs to be generated / printed for executing on another machines or for
 * debugging purposes.
 *
 * PLEASE NOTE: This driver does NOT safely quote SQL statements. This should never be
 * executed with user provided input.
 */
class NoopDriver implements DriverInterface
{
	
	/**
	 *
	 * @var LoggerInterface
	 */
	private $logger;
	
	/**
	 *
	 * @var Settings
	 */
	private $settings;
	
	
	public function __construct(Settings $settings, LoggerInterface $logger)
	{
		$this->settings = $settings;
		$this->logger   = $logger;
	}
	
	public function apply(MigrationOperationInterface $migration) : void
	{
	}
	
	public function rollback(MigrationOperationInterface $migration) : void
	{
	}
	
	public function query(Query $query): ResultSetInterface
	{
		$sql = (new MySQLQueryGrammar(new SlashQuoter()))->query($query);
		$this->logger->debug($sql);
		return new MockResultSet();
	}
	
	public function update(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar(new SlashQuoter());
		$stmt = $grammar->updateRecord($record);
		
		$this->logger->debug($stmt);
		return true;
	}
	
	public function insert(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar(new SlashQuoter());
		$stmt = $grammar->insertRecord($record);
		
		$this->logger->debug($stmt);
		return true;
	}
	
	public function delete(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar(new SlashQuoter());
		$stmt = $grammar->deleteRecord($record);
		
		$this->logger->debug($stmt);
		return true;
	}
	
	
	/**
	 * Creates a database on MySQL's side where data can be stored on behalf of
	 * the application.
	 *
	 * @return bool
	 */
	public function create(): bool
	{
		
		$this->logger->debug(sprintf('CREATE DATABASE `%s`', $this->settings->getSchema()));
		$this->logger->debug(sprintf('use `%s`;', $this->settings->getSchema()));
		return true;
	}
	
	public function has(string $name): bool
	{
		$grammar = new MySQLSchemaGrammar();
		$sql = $grammar->hasTable($this->settings->getSchema(), $name);
		
		$this->logger->debug($sql);
		return true;
	}
	
	/**
	 * Destroys the database housing the app's information.
	 *
	 * @return bool
	 */
	public function destroy(): bool
	{
		$this->logger->debug(sprintf('DROP DATABASE `%s`', $this->settings->getSchema()));
		return true;
	}
}
