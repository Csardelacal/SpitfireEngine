<?php namespace spitfire\storage\database\migration\relational;

use PDOException;
use spitfire\collection\OutOfBoundsException;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\Connection;
use spitfire\storage\database\Layout;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor as SchemaStateTableMigrationExecutor;
use spitfire\storage\database\migration\TagManagerInterface;
use spitfire\storage\database\Query;
use spitfire\storage\database\query\QueryOrTableIdentifier;
use spitfire\storage\database\Record;

class TagManager implements TagManagerInterface
{
	
	
	/**
	 *
	 * @var Connection
	 */
	private $connection;
	
	
	public function __construct(Connection $connection)
	{
		$this->connection = $connection;
	}
	
	/**
	 * Tag the database.
	 *
	 * @param string $tag
	 * @throws ApplicationException If the migration could not be applied
	 */
	public function tag(string $tag): void
	{
		$record = new Record(['tag' => $tag, 'created' => null, 'updated' => null, '_id' => null]);
		$this->connection->insert($this->connection->getSchema()->getLayoutByName('_tags'), $record);
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
		$this->connection->delete($this->connection->getSchema()->getLayoutByName('_tags'), $record);
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
		if ($this->connection->getSchema()->hasLayoutByName('_tags')) {
			$layout = $this->connection->getSchema()->getLayoutByName('_tags');
		}
		/**
		 * This is a very special case. When the tag schema is not yet consolidated, but the
		 * system already has _tags created.
		 */
		else {
			$layout = new Layout('_tags');
			
			$migrator = new SchemaStateTableMigrationExecutor($layout);
			$migrator->id();
			$migrator->string('tag', 255);
			$migrator->timestamps();
		}
		
		try {
			$query = new Query(new QueryOrTableIdentifier($layout->getTableReference()));
			$query->selectAll();
			$result = $this->connection->query($query);
			
			$_return = [];
			
			while ($_record = $result->fetchAssociative()) {
				$_return[] = $_record['tag'];
			}
			
			return $_return;
		}
		/**
		 * @todo Remove PDO Exception with something generic
		 */
		catch (PDOException $e) {
			return [];
		}
	}
}
