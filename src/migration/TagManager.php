<?php namespace spitfire\storage\database\migration;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\Query;
use spitfire\storage\database\Record;
use spitfire\storage\database\Schema;

class TagManager implements TagManagerInterface
{
	
	
	/**
	 *
	 * @var DriverInterface
	 */
	private $driver;
	
	/**
	 *
	 * @var Schema
	 */
	private $schema;
	
	public function __construct(DriverInterface $driver, Schema $schema)
	{
		$this->driver = $driver;
		$this->schema = $schema;
		
		/**
		 * To ensure that the tagging system can work at all, we need to make sure that
		 * the tag table is included.
		 */
		(new TagLayoutMigration())->up($this->driver->getMigrationExecutor($schema));
	}
	
	/**
	 * Tag the database.
	 *
	 * @param string $tag
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function tag(string $tag): void
	{
		$record = new Record(['tag' => $tag]);
		$this->driver->insert($this->schema->getLayoutByName('_tags'), $record);
	}
	
	/**
	 * Remove a tag from the database.
	 *
	 * @param string $tag
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function untag(string $tag): void
	{
		$record = new Record(['tag' => $tag]);
		$this->driver->delete($this->schema->getLayoutByName('_tags'), $record);
	}
	
	/**
	 * Lists the database's tags. Tags are used as an in-channel mechanism to keep track
	 * of the database's state, like migrations and similar.
	 *
	 * @return string[] Indicating whether the migration is already applied
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function listTags(): array
	{
		$layout = $this->schema->getLayoutByName('_tags');
		$query = new Query($layout->getTableReference());
		$result = $this->driver->query($query);
		
		$_return = [];
		
		while ($_record = $result->fetch()) {
			$_return[] = $_record['tag'];
		}
		
		return $_return;
	}
}
