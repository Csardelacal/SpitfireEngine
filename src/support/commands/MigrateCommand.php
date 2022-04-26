<?php namespace spitfire\storage\database\support\commands;

use spitfire\cli\arguments\CLIParameters;
use spitfire\mvc\Director;
use spitfire\storage\database\Connection;
use spitfire\storage\database\MigrationOperationInterface;

class MigrateCommand extends Director
{
	
	/**
	 *
	 * @var Connection
	 */
	private $connection;
	
	/**
	 *
	 * @var MigrationOperationInterface[]
	 */
	private $migrations;
	
	/**
	 *
	 * @param Connection $connection
	 * @param MigrationOperationInterface[] $migrations
	 */
	public function __construct(Connection $connection, array $migrations)
	{
		$this->connection = $connection;
		$this->migrations = $migrations;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function parameters(): array
	{
		return [];
	}
	
	/**
	 *
	 * @param string[] $parameters
	 * @param CLIParameters $arguments
	 */
	public function exec(array $parameters, CLIParameters $arguments): int
	{
		foreach	($this->migrations as $migration) {
			if (!$this->connection->contains($migration)) {
				$instance = new $migration();
				assert($instance instanceof MigrationOperationInterface);
				$instance->up($this->connection->getDriver()->getMigrationExecutor($this->connection->getSchema()));
			}
		}
		
		return 0;
	}
}
