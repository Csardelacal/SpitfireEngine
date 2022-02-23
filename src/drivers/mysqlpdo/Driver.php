<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLQuoter;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\io\CharsetEncoder;
use spitfire\storage\database\Layout;
use spitfire\storage\database\MigrationOperationInterface;
use spitfire\storage\database\Query;
use spitfire\storage\database\Record;
use spitfire\storage\database\ResultSetInterface;
use spitfire\storage\database\Settings;

/**
 * MySQL driver via PDO. This driver does <b>not</b> make use of prepared
 * statements, prepared statements become too difficult to handle for the driver
 * when using several JOINs or INs. For this reason the driver has moved from
 * them back to standard querying.
 */
class Driver implements DriverInterface
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
	
	/**
	 *
	 * @var CharsetEncoder
	 */
	private $encoder;
	
	/**
	 *
	 * @var PDO
	 */
	private $connection;
	
	
	public function __construct(Settings $settings, LoggerInterface $logger)
	{
		$this->settings = $settings;
		$this->logger   = $logger;
		$this->encoder  = new CharsetEncoder(mb_internal_encoding(), $settings->getEncoding());
		
		$encoding = ['utf8' => 'utf8mb4'][$this->encoder->getInnerEncoding()];
		
		/**
		 * Generate the DSN for the mysql PDO connection.
		 */
		$dsn  = 'mysql:' . http_build_query(array_filter(['dbname' => $settings->getSchema(), 'host' => $settings->getServer(), 'charset' => $encoding]), '', ';');
		$user = $settings->getUser();
		$pass = $settings->getPassword();
		
		/**
		 * Connect to the database to prepare for incoming queries. That way we can
		 * start receiving queries immediately.
		 */
		try {
			$this->connection = new PDO($dsn, $user, $pass);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->connection->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
		}
		catch (PDOException $e) {
			$logger->error($e->getMessage());
			throw new ApplicationException('DB Error. Connection refused by the server: ' . $e->getMessage());
		}
	}
	
	public function apply(MigrationOperationInterface $migration) : void
	{
	}
	
	public function rollback(MigrationOperationInterface $migration) : void
	{
	}
	
	public function query(Query $query): ResultSetInterface
	{
		$sql = (new MySQLQueryGrammar(new MySQLQuoter($this->connection)))->query($query);
		$res = $this->connection->query($sql);
		assert($res instanceof PDOStatement);
		return new ResultSet($this->encoder, $res);
	}
	
	public function update(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar(new MySQLQuoter($this->connection));
		$stmt = $grammar->updateRecord($record);
		
		$this->logger->debug($stmt);
		$result = $this->connection->exec($stmt);
		
		return $result !== false;
	}
	
	public function insert(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar(new MySQLQuoter($this->connection));
		$stmt = $grammar->insertRecord($record);
		
		$this->logger->debug($stmt);
		$result = $this->connection->exec($stmt);
		
		return $result !== false;
	}
	
	public function delete(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar(new MySQLQuoter($this->connection));
		$stmt = $grammar->deleteRecord($record);
		
		$this->logger->debug($stmt);
		$result = $this->connection->exec($stmt);
		
		return $result !== false;
	}
	
	
	/**
	 * Creates a database on MySQL's side where data can be stored on behalf of
	 * the application.
	 *
	 * @return bool
	 */
	public function create(): bool
	{
		
		try {
			$this->connection->exec(sprintf('CREATE DATABASE `%s`', $this->settings->getSchema()));
			$this->connection->exec(sprintf('use `%s`;', $this->settings->getSchema()));
			return true;
		}
		/*
		 * Sometimes the database will issue a FileNotFound exception when attempting
		 * to connect to a DBMS that fails if the database it expected to connect
		 * to is not available.
		 *
		 * In this event we create a new connection that ignores the schema setting,
		 * therefore allowing to connect to the database properly.
		 */
		catch (PDOException $e) {
			return false;
		}
	}
	
	public function has(string $name): bool
	{
		$grammar = new MySQLSchemaGrammar();
		$stmt = $this->connection->query($grammar->hasTable($this->settings->getSchema(), $name));
		
		assert($stmt instanceof PDOStatement);
		return ($stmt->fetch()[0]) > 0;
	}
	
	/**
	 * Destroys the database housing the app's information.
	 *
	 * @return bool
	 */
	public function destroy(): bool
	{
		$this->connection->exec(sprintf('DROP DATABASE `%s`', $this->settings->getSchema()));
		return true;
	}
}
