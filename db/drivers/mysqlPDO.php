<?php namespace spitfire\storage\database\drivers;

use Schema;
use spitfire\storage\database\DB;
use spitfire\SpitFire;
use PDO;
use PDOException;
use spitfire\exceptions\PrivateException;

/**
 * MySQL driver via PDO. This driver does <b>not</b> make use of prepared 
 * statements, prepared statements become too difficult to handle for the driver
 * when using several JOINs or INs. For this reason the driver has moved from
 * them back to standard querying.
 */
class mysqlPDODriver extends DB
{

	private $connection    = false;
	
	/**@var mixed List of errors the repair() method can fix. This include:
	 *     <ul>
	 *     <li>1051 - Unknown table.</li>
	 *     <li>1054 - Unknown column</li>
	 *     <li>1146 - No such table</li>
	 *     </ul>
	 */
	private $reparable_errors = Array(1051, 1054, 1146);
	
	
	/**
	 * Establishes the connection with the database server. This function
	 * requires no parameters as they're stored by the class already.
	 * 
	 * @return boolean
	 * @throws PrivateException If the database was unable to establish a 
	 *                          connection because the Server rejected the connection.
	 */
	protected function connect() {
		
		$dsn  = 'mysql:dbname=' . $this->schema . ';host=' . $this->server . ';charset=' . $this->getEncoder()->getInnerEncoding();
		$user = $this->user;
		$pass = $this->password;

		try {
			$this->connection = new PDO($dsn, $user, $pass);
			$this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$this->connection->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
			
			return true;
		} catch (Exception $e) {
			SpitFire::$debug->log($e->getMessage());
			throw new PrivateException('DB Error. Connection refused by the server');
		}

	}

	/**
	 * Checks if a connection to the DB exists and creates it in case it
	 * does not exist already.
	 * 
	 * @return PDO
	 */
	public function getConnection() {
		if (!$this->connection) $this->connect();
		return $this->connection;
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
			#Recover from exception, make error readable. Re-throw
			$code = $e->getCode();
			$err  = $e->errorInfo;
			$msg  = $err[2]? $err[2] : 'Unknown error';
			
			#If the error is not repairable or the system is blocking repairs throw an exception
			if (!in_array($err[1], $this->reparable_errors) || !$attemptrepair) 
				{ throw new PrivateException("Error {$code} [{$msg}] captured. Not repairable", 1511081930, $e); }
			
			#Try to solve the error by checking integrity and repeat
			$this->repair();
			return $this->execute($statement, $parameters, false);
		}
	}
	
	/**
	 * Creates a new Table and returns it.
	 * 
	 * @deprecated since version 0.1-dev 1607020148
	 * @param spitfire.storage.database.DB $db
	 * @param string $tablename
	 * @param Schema $model
	 * @return spitfire.storage.database.drivers.MysqlPDOTable
	 */
	public function getTableInstance(DB$db, $tablename) {
		trigger_error('Deprecated: Use object factory instead', E_USER_DEPRECATED);
		return new MysqlPDOTable($db, $tablename);
	}
	
	/**
	 * Escapes a string to be used in a SQL statement. PDO offers this
	 * functionality out of the box so there's nothing to do.
	 * 
	 * @param string $text
	 * @return string Quoted and escaped string
	 */
	public function quote($text) {
		if ($text === null)  return 'null';
		if ($text ===    0)  return "'0'";
		if ($text === false) return "'0'";
		
		$str = $this->getEncoder()->encode($text);
		return $this->getConnection()->quote( $str );
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 1607020148
	 * @param type $tablename
	 * @return \OTFModel
	 */
	public function getOTFModel($tablename) {
		trigger_error('Deprecated: Use object factory instead', E_USER_DEPRECATED);
		return $this->getObjectFactory()->getOTFModel($tablename);
	}
	
	/**
	 * 
	 * @staticvar \storage\database\drivers\mysqlpdo\ObjectFactory $factory
	 * @return  \storage\database\drivers\mysqlpdo\ObjectFactory
	 */
	public function getObjectFactory() {
		static $factory;
		return $factory? : $factory = new \spitfire\storage\database\drivers\mysqlpdo\ObjectFactory();
	}
}
