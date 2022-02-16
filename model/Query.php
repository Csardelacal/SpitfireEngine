<?php namespace spitfire\model;

use BadMethodCallException;
use spitfire\model\query\Queriable;
use spitfire\model\query\RestrictionGroup;
use spitfire\storage\database\Connection;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\Query as DatabaseQuery;

/**
 *
 */
class Query
{
	
	use Queriable;
	
	private $model;
	
	private $with;
	
	/**
	 *
	 * @var DatabaseQuery
	 */
	private $query;
	
	public function __construct(Model $model)
	{
		$this->model = $model;
		
		$this->query = new DatabaseQuery($this->model->getTable()->getTableReference());
	}
	
	public function getQuery() : DatabaseQuery
	{
		return $this->query;
	}
	
	public function getModel() : Model
	{
		return $this->model;
	}
	
	public function first(callable $or = null)
	{
	}
	
	public function all()
	{
		$this->query->selectAll();
		$this->db->query($this->query);
	}
	
	public function range(int $offset, int $size)
	{
	}
}
