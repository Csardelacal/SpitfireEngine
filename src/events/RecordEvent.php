<?php namespace spitfire\storage\database\events;

use spitfire\event\Event;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\Record;

abstract class RecordEvent extends Event
{
	
	/**
	 * 
	 * @var DriverInterface
	 */
	private $driver;
	
	/**
	 * 
	 * @var Record
	 */
	private $record;
	
	/**
	 * 
	 * @var mixed[]
	 */
	private $options;
	
	/**
	 * 
	 * @param DriverInterface $driver
	 * @param Record $record
	 * @param string[] $options
	 */
	public function __construct(DriverInterface $driver, Record $record, array $options = [])
	{
		$this->record = $record;
		$this->options = $options;
		$this->driver = $driver;
	}
	
	public function getRecord() : Record
	{
		return $this->record;
	}
	
	/**
	 * 
	 * @return string[]
	 */
	public function getOptions() : array
	{
		return $this->options;
	}
	
	public function getDriver() : DriverInterface
	{
		return $this->driver;
	}
}
