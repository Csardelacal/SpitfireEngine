<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\Field;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLQuoter;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\io\CharsetEncoder;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\Query;
use spitfire\storage\database\Record;
use spitfire\storage\database\ResultSetInterface;
use spitfire\storage\database\Schema;
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
	
	/**
	 *
	 * @var LoggerInterface
	 */
	private $logger;
	
	/**
	 *
	 * @var int
	 */
	private $mode = DriverInterface::MODE_EXC;
	
	
	public function __construct(Settings $settings, LoggerInterface $logger)
	{
		$this->settings = $settings;
		$this->logger   = $logger;
		$this->encoder  = new CharsetEncoder(mb_internal_encoding(), $settings->getEncoding());
	}
	
	public function init() : void
	{
		/**
		 * If the driver isn't hot, we assume the connection is not expected.
		 */
		if (!($this->mode & DriverInterface::MODE_EXC)) {
			return;
		}
		
		$encoding = ['utf8' => 'utf8mb4'][$this->encoder->getInnerEncoding()];
		
		/**
		 * Generate the DSN for the mysql PDO connection.
		 */
		$dsn  = 'mysql:' . http_build_query(array_filter([
			'dbname' => $this->settings->getSchema(),
			'host' => $this->settings->getServer(),
			'charset' => $encoding
		]), '', ';');
		
		$user = $this->settings->getUser();
		$pass = $this->settings->getPassword();
		
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
			$this->logger->error($e->getMessage());
			throw new ApplicationException('DB Error. Connection refused by the server: ' . $e->getMessage());
		}
	}
	
	public function getMigrationExecutor(Schema $schema): SchemaMigrationExecutorInterface
	{
		return new SchemaMigrationExecutor($this->connection, $schema);
	}
	
	public function query(Query $query): ResultSetInterface
	{
		$sql = (new MySQLQueryGrammar(new MySQLQuoter($this->connection)))->query($query);
		$res = $this->_query($sql);
		assert($res instanceof PDOStatement);
		return new ResultSet($this->encoder, $res);
	}
	
	public function update(LayoutInterface $layout, Record $record): bool
	{
		$grammar = new MySQLRecordGrammar(new MySQLQuoter($this->connection));
		$stmt = $grammar->updateRecord($layout, $record);
		
		$this->logger->debug($stmt);
		$result = $this->_exec($stmt);
		$record->commit();
		
		return $result !== false;
	}
	
	public function insert(LayoutInterface $layout, Record $record): bool
	{
		$grammar = new MySQLRecordGrammar(new MySQLQuoter($this->connection));
		$stmt = $grammar->insertRecord($layout, $record);
		
		$this->logger->debug($stmt);
		$result = $this->_exec($stmt);
		
		/**
		 * In the event that the field is automatically incremented, the dbms
		 * will provide us with the value it inserted. This value needs to be
		 * stored to the record.
		 */
		$increment = $layout->getFields()->filter(function (Field $field) {
			return $field->isAutoIncrement();
		})->first();
		
		if ($increment !== null) {
			$id = $this->connection->lastInsertId();
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
		$grammar = new MySQLRecordGrammar(new MySQLQuoter($this->connection));
		$stmt = $grammar->deleteRecord($layout, $record);
		
		$this->logger->debug($stmt);
		$result = $this->_exec($stmt);
		
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
			$this->_exec(sprintf('CREATE DATABASE `%s`', $this->settings->getSchema()));
			$this->_exec(sprintf('use `%s`;', $this->settings->getSchema()));
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
		$stmt = $this->_query($grammar->hasTable($this->settings->getSchema(), $name));
		
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
		$this->_exec(sprintf('DROP DATABASE `%s`', $this->settings->getSchema()));
		return true;
	}
	
	public function mode(?int $mode = null): int
	{
		
		if ($mode !== null) {
			$this->mode = $mode;
		}
		
		return $this->mode;
	}
	
	private function _exec(string $sql) : int|false
	{
		
		if ($this->mode & DriverInterface::MODE_PRT) {
			echo $sql, PHP_EOL;
		}
		
		if ($this->mode & DriverInterface::MODE_LOG) {
			$this->logger->debug($sql);
		}
		
		if ($this->mode & DriverInterface::MODE_DBG) {
			xdebug_break();
		}
		
		if ($this->mode & DriverInterface::MODE_EXC) {
			return $this->connection->exec($sql);
		}
		
		return false;
	}
	
	private function _query(string $sql) : PDOStatement|false
	{
		
		if ($this->mode & DriverInterface::MODE_PRT) {
			echo $sql, PHP_EOL;
		}
		
		if ($this->mode & DriverInterface::MODE_LOG) {
			$this->logger->debug($sql);
		}
		
		if ($this->mode & DriverInterface::MODE_DBG) {
			xdebug_break();
		}
		
		if ($this->mode & DriverInterface::MODE_EXC) {
			return $this->connection->query($sql);
		}
		
		return false;
	}
}
