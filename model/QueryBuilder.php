<?php namespace spitfire\model;

use spitfire\collection\Collection;
use spitfire\model\query\Queriable;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSetMapping;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\Query as DatabaseQuery;

/**
 *
 */
class QueryBuilder
{
	
	use Queriable;
	
	private $db;
	
	private $model;
	
	/**
	 *
	 * @var Collection<ResultSetMapping>
	 */
	private $mappings;
	
	/**
	 * The with method allows the user to determine relations that should be
	 * proactively resolved.
	 *
	 * @var string[]
	 */
	private $with = [];
	
	/**
	 *
	 * @var DatabaseQuery
	 */
	private $query;
	
	public function __construct(Model $model)
	{
		$this->db = $model->getConnection();
		$this->model = $model;
		
		$this->query = new DatabaseQuery($this->model->getTable()->getTableReference());
		$this->mappings = new Collection();
	}
	
	public function withDefaultMapping() : QueryBuilder
	{
		$copy = clone $this;
		
		/**
		 * We need to select all the fields from the table we're querying to push them into
		 * our model so it can be hydrated.
		 */
		$copy->query->selectAll();
		
		/**
		 * Extract the name of the fields so we can assign it back to the generic mapping
		 * that will read the data from the query into the model.
		 */
		$fields = $copy->model->getTable()->getFields()->extract('getName')->toArray();
		
		$copy->mappings->push(new ResultSetMapping($this->model, array_combine($fields, $fields)));
		
		return $copy;
	}
	
	public function withMapping(ResultSetMapping $mapping) : QueryBuilder
	{
		$copy = clone $this;
		$copy->mappings->push($mapping);
		return $copy;
	}
	
	public function getQuery()
	{
		return $this->query;
	}
	
	public function getModel()
	{
		return $this->model;
	}
	
	/**
	 * 
	 * @param string $type
	 * @param callable(RestrictionGroupBuilder) $do
	 * @return QueryBuilder
	 */
	public function group(string $type, callable $do) : QueryBuilder
	{
		$group = $this->query->getRestrictions()->group($type);
		$do(new RestrictionGroupBuilder($this, $group));
		return $this;
	}
	
	/**
	 * Pass an array of strings with relationships that should be eagerly
	 * loaded when retrieving data.
	 *
	 * @param string[] $with
	 * @return self
	 */
	public function with(array $with)
	{
		$this->with = $with;
		return $this;
	}
	
	public function first(callable $or = null):? Model
	{
	}
	
	public function all() : Collection
	{
		$result = $this->model->getConnection()->getDriver()->query($this->withDefaultMapping()->getQuery())->fetchAll();
		return new Collection();
	}
	
	public function range(int $offset, int $size) : Collection
	{
	}
	
	public function count() : int
	{
		$query = $this->query->withoutSelect();
		
		$query->aggregate(
			$this->getQuery()->getFrom()->output()->getOutput('_id'),
			new Aggregate(Aggregate::AGGREGATE_COUNT),
			'c'
		);
		
		$res = $this->model->getConnection()->getDriver()->query($query)->fetch();
		return $res['c'];
	}
}
