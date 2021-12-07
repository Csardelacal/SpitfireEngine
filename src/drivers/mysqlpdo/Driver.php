<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use PDO;
use PDOException;
use PDOStatement;
use Psr\Log\LoggerInterface;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\FileNotFoundException;
use spitfire\exceptions\PrivateException;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\io\CharsetEncoder;
use spitfire\storage\database\MigrationOperationInterface;
use spitfire\storage\database\Query;
use spitfire\storage\database\Record;
use spitfire\storage\database\ResultSetInterface;
use spitfire\storage\database\Settings;

use function spitfire;

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
	
	public function migrate(MigrationOperationInterface $migration)
	{
		
	}
	
	public function query(Query $query): ResultSetInterface
	{
		
	}
	
	public function update(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar($this->connection);
		$stmt = $grammar->updateRecord($record);
		
		$this->logger->debug($stmt);
		$result = $this->connection->exec($stmt);
		
		return $result !== false;
	}
	
	public function insert(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar($this->connection);
		$stmt = $grammar->insertRecord($record);
		
		$this->logger->debug($stmt);
		$result = $this->connection->exec($stmt);
		
		return $result !== false;
	}
	
	public function delete(Record $record): bool
	{
		$grammar = new MySQLRecordGrammar($this->connection);
		$stmt = $grammar->deleteRecord($record);
		
		$this->logger->debug($stmt);
		$result = $this->connection->exec($stmt);
		
		return $result !== false;
	}
	
	/**
	 * Sends a query to the database server and returns the handle for the
	 * resultset the server / native driver returned.
	 * 
	 * @param string $statement SQL to be executed by the server.
	 * @param boolean $attemptrepair Defines whether the server should try
	 *                    to repair any model inconsistencies the server 
	 *                    encounters.
	 * @return PDOStatement
	 * @throws PrivateException In case the query fails for another reason
	 *                     than the ones the system manages to fix.
	 */
	public function execute($statement, $parameters = Array(), $attemptrepair = true) {
		#Connect to the database and prepare the statement
		$con = $this->getConnection();
		
		try {
			spitfire()->log("DB: " . $statement);
			#Execute the query
			$stt = $con->prepare($statement);
			$stt->execute();
			
			return $stt;
		
		} catch(PDOException $e) {
			#Log the error that happened.
			spitfire()->log("Captured: {$e->getCode()} - {$e->getMessage()}");
			#Recover from exception, make error readable. Re-throw
			$code = $e->getCode();
			$err  = $e->errorInfo;
			$msg  = $err[2]? $err[2] : 'Unknown error';
			
			#If the error is not repairable or the system is blocking repairs throw an exception
			if (!in_array($err[1], $this->reparableErrors) || !$attemptrepair) 
				{ throw new PrivateException("Error {$code} [{$msg}] captured. Not repairable", 1511081930, $e); }
			
			#Try to solve the error by checking integrity and repeat
			$this->repair();
			return $this->execute($statement, $parameters, false);
		}
	}
	
	/**
	 * Escapes a string to be used in a SQL statement. PDO offers this
	 * functionality out of the box so there's nothing to do.
	 * 
	 * @param string $text
	 * @return string Quoted and escaped string
	 */
	public function quote($text) {
		if ($text === null)  { return 'null'; }
		if ($text ===    0)  { return "'0'";  }
		if ($text === false) { return "'0'";  }
		
		$str = $this->encoder->encode($text); //This statement should not be here.
		//It's not part of the quoting mechanism to encode the data.
		
		return $this->connection->quote( $str );
	}
	
	/**
	 * 
	 * @staticvar \storage\database\drivers\mysqlpdo\ObjectFactory $factory
	 * @return  ObjectFactory
	 */
	public function getObjectFactory() {
		static $factory;
		return $factory? : $factory = new ObjectFactory();
	}

	/**
	 * Creates a database on MySQL's side where data can be stored on behalf of
	 * the application.
	 * 
	 * @return bool
	 */
	public function create(): bool {
		
		try {
			$this->execute(sprintf('CREATE DATABASE `%s`', $this->getSettings()->getSchema()));
			$this->execute(sprintf('use `%s`;', $this->getSettings()->getSchema()));
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
		catch (FileNotFoundException$e) {
			#Modify the connection settings, removing the schema.
			$settings = clone $this->getSettings();
			$settings->setSchema('');
			
			#Establish the new connection
			$db = new Driver($settings);
			$db->getConnection();
			
			#Set the schema and run a retry
			$settings->setSchema($this->getSettings()->getSchema());
			$db->create();
			return true;
		}
		
		return false;
	}
	
	/**
	 * Destroys the database housing the app's information.
	 * 
	 * @return bool
	 */
	public function destroy(): bool {
		$this->execute(sprintf('DROP DATABASE `%s`', $this->getSettings()->getSchema()));
		return true;
	}

}
