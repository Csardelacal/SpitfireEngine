<?php namespace spitfire\storage\database\drivers\internal;

use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\MigrationOperationInterface;
use spitfire\storage\database\Query;
use spitfire\storage\database\Record;
use spitfire\storage\database\ResultSetInterface;
use spitfire\storage\database\Schema;

class MockDriver implements DriverInterface
{
	
	/**
	 *
	 * @var array<int,array<int|string,mixed>>
	 */
	private $operations = [];
	
	public function apply(MigrationOperationInterface $migration): void
	{
		$this->operations[] = ['migration', get_class($migration)];
	}
	
	public function rollback(MigrationOperationInterface $migration): void
	{
		$this->operations[] = ['migration:rollback', get_class($migration)];
	}
	
	public function query(Query $query): ResultSetInterface
	{
		$this->operations[] = ['query', ''];
		return new MockResultSet();
	}
	
	public function update(LayoutInterface $layout, Record $record): bool
	{
		$this->operations[] = ['update', $record->diff()];
		$record->commit();
		return true;
	}
	
	public function insert(LayoutInterface $layout, Record $record): bool
	{
		$this->operations[] = ['insert', $record->raw()];
		$record->commit();
		return true;
	}
	
	public function delete(LayoutInterface $layout, Record $record): bool
	{
		assert($layout->getPrimaryKey() !== null);
		assert($layout->getPrimaryKey()->getFields()->first() !== null);
		
		$this->operations[] = ['delete', $record->get($layout->getPrimaryKey()->getFields()->first()->getName())];
		return true;
	}
	
	public function create() : bool
	{
		$this->operations[] = ['create', ''];
		return true;
	}
	
	public function destroy() : bool
	{
		$this->operations[] = ['destroy', ''];
		return true;
	}
	
	public function has(string $name): bool
	{
		return false;
	}
	
	public function getMigrationExecutor(Schema $schema): SchemaMigrationExecutorInterface
	{
		return new SchemaMigrationExecutor($schema);
	}
	
	/**
	 *
	 * @return array<int,array<int|string,mixed>>
	 */
	public function getLog() : array
	{
		return $this->operations;
	}
}
