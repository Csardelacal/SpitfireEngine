<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use PDO;
use PDOStatement;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\io\CharsetEncoder;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\Record;
use spitfire\storage\database\ResultSetInterface;
use spitfire\storage\database\Table;

/**
 * This class works as a traditional resultset. It acts as an adapter between the
 * driver's raw data retrieving and the logical record classes.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class ResultSet implements ResultSetInterface
{
	/**
	 * Contains the raw pointer that PDO has created when executing the query.
	 * This allows spitfire to retrieve all the data needed to create a complete
	 * database record.
	 *
	 * @var PDOStatement
	 */
	private $result;
	
	/**
	 * This is a reference to the table this resultset belongs to. This allows
	 * Spitfire to retrieve data about the model and the fields the datatype has.
	 *
	 * @var LayoutInterface
	 */
	private $table;
	
	/**
	 * An encoder that converts the data from the DB charset, to the internal charset.
	 * 
	 * @var CharsetEncoder
	 */
	private $encoder;
	
	public function __construct(CharsetEncoder $encoder, LayoutInterface $table, PDOStatement $stt) 
	{
		$this->result = $stt;
		$this->encoder = $encoder;
		$this->table = $table;
	}
	
	/**
	 * 
	 * @return Collection<Record>
	 */
	public function fetchAll() : Collection
	{
		$data = $this->result->fetchAll(PDO::FETCH_ASSOC);
		
		/**
		 * We cannot accept the application being unable to retrieve data from the server.
		 */
		if ($data === false) {
			throw new ApplicationException('DB Error: Could not fetch data', 2101181256);
		}
		
		foreach ($data as &$record) {
			$record = new Record(
				$this->table,
				array_map( Array($this->encoder, 'decode'), $record)
			);
		}
		
		return new Collection($data);
	}
	
	/**
	 * Returns the data the way any associative adapter would return it. This allows
	 * your app to withdraw raw data without it being treated by the framework.
	 * 
	 * @return mixed
	 */
	public function fetch(): ?array
	{
		return $this->result->fetch(PDO::FETCH_ASSOC);
	}
	
}
