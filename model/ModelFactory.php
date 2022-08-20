<?php namespace spitfire\model;

use ReflectionClass;
use spitfire\storage\database\Connection;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\Record;

/**
 *
 * @todo Extrapolate ModelFactoryInterface
 */
class ModelFactory
{
	
	private ConnectionInterface $connection;
	
	public function __construct(ConnectionInterface $connection)
	{
		$this->connection = $connection;
	}
	
	/**
	 *
	 * @param class-string $className
	 * @todo Caching would probably help this gain some performance
	 */
	public function make(string $className) : Model
	{
		$reflection = new ReflectionClass($className);
		assert($reflection->isSubclassOf(Model::class));
		return $reflection->newInstance($this->connection);
	}
	
	/**
	 *
	 * @param class-string $className
	 */
	public function from(string $className) : QueryBuilder
	{
		return (new QueryBuilder($this->make($className)))->withDefaultMapping();
	}
	
	/**
	 *
	 * @param string $className
	 * @param int|string $id
	 * @return Model|null
	 */
	public function fetch(string $className, $id) :? Model
	{
		$model = $this->make($className);
		$query = (new QueryBuilder($model))->withDefaultMapping();
		$query->where($model->getTable()->getPrimaryKey()->getFields()->first()->getName(), $id);
		return $query->first();
	}
	
	/**
	 *
	 * @param class-string $className
	 */
	public function create(string $className) : Model
	{
		$model  = $this->make($className);
		$empty  = [];
		$layout = $this->connection->getSchema()->getLayoutByName($model->getTableName());
		
		foreach ($layout->getFields() as $field) {
			/**
			 * @todo Adding defaults here would be super fly. But this depends on the fields and migrators
			 * providing support for defaults so we can pull them here.
			 */
			$empty[$field->getName()] = null;
		}
		
		$record = new Record($empty);
		return $model->withHydrate(new ActiveRecord($model, $record));
	}
}
