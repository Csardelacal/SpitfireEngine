<?php namespace spitfire\model;

use BadMethodCallException;
use spitfire\model\query\Queriable;
use spitfire\model\query\RestrictionGroup;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\Query as DatabaseQuery;

/**
 *
 */
class Query
{
	
	use Queriable;
	
	private $db;
	
	private $model;
	
	private $with;
	
	/**
	 *
	 * @var DatabaseQuery
	 */
	private $query;
	
	public function __construct(DriverInterface $db, Model $model)
	{
		$this->db = $db;
		$this->model = $model;
		
		$this->query = new DatabaseQuery($this->model->getTable());
		$this->query->selectAll();
	}
	
	public function getQuery()
	{
		return $this->query;
	}
	
	public function first(callable $or = null)
	{
	}
	
	public function all()
	{
	}
	
	public function range(int $offset, int $size)
	{
	}
}
