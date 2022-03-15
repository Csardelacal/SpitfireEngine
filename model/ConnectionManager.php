<?php namespace spitfire\model;

use Psr\Log\LoggerInterface;
use spitfire\provider\Container;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\mysqlpdo\Driver as MySQLDriver;
use spitfire\storage\database\Settings;

class ConnectionManager
{
	
	/**
	 * 
	 * @var string|null
	 */
	private $default;
	
	/**
	 * 
	 * @var Connection[]
	 */
	private $connections = [];
	
	/**
	 * 
	 * @var Container
	 */
	private $provider;
	
	public function __construct(Container $provider)
	{
		$this->provider = $provider;
	}
	
	
	public function put(string $name, Connection $driver) : void
	{
		$this->connections[$name] = $driver;
	}
	
	public function make(string $name, Settings $settings) : void
	{
		if ($settings->getDriver() === 'mysqlpdo')
		{
			$driver = new MySQLDriver($settings, $this->provider->get(LoggerInterface::class));
			$this->connections[$name] = $driver;
		}
	}
	
	public function get(string $name) : Connection
	{
		assert(array_key_exists($name, $this->connections));
		return $this->connections[$name];
	}
	
	public function setDefault(string $default)
	{
		assert(array_key_exists($default, $this->connections));
		return $this->default = $default;
	}
	
	public function getDefault()
	{
		assert($this->default !== null);
		return $this->connections[$this->default];
	}
}