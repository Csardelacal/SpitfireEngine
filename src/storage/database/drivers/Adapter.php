<?php

namespace spitfire\storage\database\drivers;

use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\grammar\QueryGrammarInterface;
use spitfire\storage\database\grammar\RecordGrammarInterface;
use spitfire\storage\database\grammar\SchemaGrammarInterface;

/**
 * An adapter connects the driver with grammar.
 */
class Adapter
{
	
	/**
	 *
	 * @var DriverInterface
	 */
	private $driver;
	
	/**
	 *
	 * @var QueryGrammarInterface|null
	 */
	private $queryGrammar;
	
	/**
	 *
	 * @var RecordGrammarInterface|null
	 */
	private $recordGrammar;
	
	/**
	 *
	 * @var SchemaGrammarInterface|null
	 */
	private $schemaGrammar;
	
	public function __construct(
		DriverInterface $driver,
		QueryGrammarInterface $queryGrammar = null,
		RecordGrammarInterface $recordGrammar = null,
		SchemaGrammarInterface $schemaGrammar = null
	) {
		$this->driver = $driver;
		$this->queryGrammar = $queryGrammar ?: $driver->getDefaultQueryGrammar();
		$this->recordGrammar = $recordGrammar ?: $driver->getDefaultRecordGrammar();
		$this->schemaGrammar = $schemaGrammar ?: $driver->getDefaultSchemaGrammar();
	}
	
	/**
	 * Get the value of driver
	 */
	public function getDriver() : DriverInterface
	{
		return $this->driver;
	}
	
	/**
	 * Set the value of driver
	 *
	 * @return  self
	 */
	public function setDriver(DriverInterface $driver)
	{
		$this->driver = $driver;
		return $this;
	}
	
	/**
	 * Get the value of schemaGrammar
	 */
	public function getSchemaGrammar() : SchemaGrammarInterface
	{
		assert($this->schemaGrammar !== null);
		return $this->schemaGrammar;
	}
	
	/**
	 * Set the value of schemaGrammar
	 *
	 * @return  self
	 */
	public function setSchemaGrammar(SchemaGrammarInterface $schemaGrammar)
	{
		$this->schemaGrammar = $schemaGrammar;
		return $this;
	}
	
	/**
	 * Get the value of queryGrammar
	 */
	public function getQueryGrammar() : QueryGrammarInterface
	{
		assert($this->queryGrammar !== null);
		return $this->queryGrammar;
	}
	
	/**
	 * Set the value of queryGrammar
	 *
	 * @return  self
	 */
	public function setQueryGrammar(QueryGrammarInterface $queryGrammar)
	{
		$this->queryGrammar = $queryGrammar;
		return $this;
	}
	
	/**
	 * Get the value of recordGrammar
	 */
	public function getRecordGrammar() : RecordGrammarInterface
	{
		assert($this->recordGrammar !== null);
		return $this->recordGrammar;
	}
	
	/**
	 * Set the value of recordGrammar
	 *
	 * @return  self
	 */
	public function setRecordGrammar(RecordGrammarInterface $recordGrammar)
	{
		$this->recordGrammar = $recordGrammar;
		return $this;
	}
}
