<?php namespace spitfire\model;

use spitfire\collection\Collection;
use spitfire\exceptions\user\ApplicationException;
use spitfire\model\query\Queriable;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSetMapping;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\Record;

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
	 * @var ResultSetMapping
	 */
	private $mapping;
	
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
		$this->makeMapping();
	}
	
	/**
	 * Defines a mapping where the fields of the database are directly mapped to the fields of
	 * the database record.
	 * 
	 * @todo
	 * In this iteration of the query builder, the system just isn't advanced enough to allow
	 * for custom queries for returning wild mapped queries. In future revisions, the model should
	 * be able to define mappings so that joined data can be retrieved alongside the query.
	 * 
	 * This would be useful for a model like employee that has a belongsToOne relationship with
	 * a relatinon like Department. In this case, the application could assemble a mapping that
	 * allows SQL to fetch a single record for both models and map them, reducing the need for 
	 * round trips to the database.
	 */
	public function makeMapping() : void
	{
		/**
		 * We need to select all the fields from the table we're querying to push them into
		 * our model so it can be hydrated.
		 */
		$this->query->selectAll();
		
		/**
		 * Extract the name of the fields so we can assign it back to the generic mapping
		 * that will read the data from the query into the model.
		 * 
		 * @var string[]
		 */
		$fields = $this->model->getTable()->getFields();
		$names  = $fields->extract('getName')->toArray();
		
		$this->mapping = new ResultSetMapping($this->model, array_combine($fields, $fields));
	}
	
	public function getQuery() : DatabaseQuery
	{
		return $this->query;
	}
	
	public function getModel() : Model
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
		$query  = clone ($this->query)->range(0, 1);
		$result = $this->model->getConnection()->getDriver()->query($query)->fetchAll();
		
		$record = $this->eagerLoad($result->each(function ($read) {
			return $this->mapping->make($read->raw());
		}))->first();
		
		if ($record === null && $or !== null) {
			if (is_string($or)) { throw new $or('No records found'); }
			if (is_callable($or)) { return $or(); }
			throw new ApplicationException('No record found');
		}
		
		assert($record instanceof $this->model);
		
		return $record;
	}
	
	public function all() : Collection
	{
		$result = $this->model->getConnection()->getDriver()->query($this->getQuery())->fetchAll();
		
		return $this->eagerLoad($result->each(function (Record $read) {
			return $this->mapping->make($read->raw());
		}));
	}
	
	public function range(int $offset, int $size) : Collection
	{
		$query  = clone ($this->query)->range($offset, $size);
		$result = $this->model->getConnection()->getDriver()->query($query)->fetchAll();
		
		return $this->eagerLoad($result->each(function ($read) {
			return $this->mapping->make($read->raw());
		}));
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
	
	public function __clone()
	{
		$this->query = clone $this->query;
	}
}
