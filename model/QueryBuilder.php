<?php namespace spitfire\model;

use spitfire\collection\Collection;
use spitfire\model\query\Queriable;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSetMapping;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\identifiers\FieldIdentifier;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\SelectExpression;
use spitfire\storage\database\Record;

/**
 *
 */
class QueryBuilder
{
	
	use Queriable;
	
	/**
	 *
	 * @var Model
	 */
	private $model;
	
	/**
	 *
	 * @var ResultSetMapping
	 */
	private ResultSetMapping $mapping;
	
	/**
	 *
	 * @var Collection<ResultSetMapping>
	 */
	private Collection $pivots;
	
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
		$this->model = $model;
		
		$this->query = new DatabaseQuery($this->model->getTable()->getTableReference());
		$this->pivots = new Collection();
	}
	
	public function withDefaultMapping() : QueryBuilder
	{
		$copy = clone $this;
		
		/**
		 * We need to select all the fields from the table we're querying to push them into
		 * our model so it can be hydrated.
		 */
		$selected = $copy->query->selectAll();
		
		/**
		 * Extract the name of the fields so we can assign it back to the generic mapping
		 * that will read the data from the query into the model.
		 *
		 * @var Collection<FieldIdentifier>
		 */
		$fields = $selected->each(fn(SelectExpression $e) => $e->getInput());
		
		$map = new ResultSetMapping($this->model);
		
		foreach ($fields as $_f) {
			$map->set($_f->raw()[0], $_f);
		}
		
		$copy->mapping = $map;
		
		return $copy;
	}
	
	public function withMapping(ResultSetMapping $mapping) : QueryBuilder
	{
		$copy = clone $this;
		$copy->mapping = $mapping;
		return $copy;
	}
	
	public function withPivot(ResultSetMapping $mapping) : QueryBuilder
	{
		$copy = clone $this;
		$copy->pivots->push($mapping);
		return $copy;
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
	 * @param callable(RestrictionGroupBuilder):void $do
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
	
	/**
	 *
	 * @param callable():Model|null $or This function can either: return null, return a model
	 * or throw an exception
	 * @return Model|null
	 */
	public function first(callable $or = null):? Model
	{
		/*
		 * Fetch a single row from the database.
		 */
		$result = $this->model->getConnection()->query($this->getQuery());
		$row    = $result->fetchAssociative();
		
		/**
		 * If there is no more rows in the result (alas, there have never been any), the application
		 * should call the or() callable. This can either create a new record, return null or throw
		 * a user defined exception.
		 */
		if ($row === false) {
			return $or === null? null : $or();
		}
		
		/**
		 * @todo Add the mapping logic here. We probably need to split the maps into main and pivots so we can
		 * differentiate properly.
		 */
		
		return $this->eagerLoad(new Collection([$this->mapping->make(new Record($row))]))->first();
	}
	
	/**
	 *
	 * @return Collection<Model>
	 */
	public function all() : Collection
	{
		/**
		 *
		 * @todo implement
		 */
		$result = $this->model->getConnection()->query($this->withDefaultMapping()->getQuery());
		return new Collection();
	}
	
	public function range(int $offset, int $size) : Collection
	{
		/*
		 * Fetch a single row from the database.
		 */
		$result = $this->model->getConnection()->query($this->getQuery());
		$rows   = $result->fetchAllAssociative();
		
		return $this->eagerLoad((new Collection($rows))->each(function (array $row) : Model {
			
			/**
			 * @todo Add the mapping logic here. We probably need to split the maps into main and pivots so we can
			 * differentiate properly.
			 */
			return $this->mapping->make(new Record($row));
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
		
		return $this->model->getConnection()->query($query)->fetchOne();
	}
	
	/**
	 *
	 * @param Collection<Model> $records
	 */
	protected function eagerLoad(Collection $records) : Collection
	{
		foreach ($this->with as $relation) {
			$meta = $this->model->$relation();
			assert($meta instanceof RelationshipInterface);
			
			$children = $meta->eagerLoad($records);
			
			/**
			 * @todo This needs to make use of reflection so it can be used properly.
			 */
			foreach ($records as $record) {
				$record->{$relation} = $children[$record->getPrimary()];
			}
		}
		
		return $records;
	}
}
