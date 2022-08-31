<?php namespace spitfire\model;

use spitfire\collection\Collection;
use spitfire\model\query\ExtendedRestrictionGroupBuilder;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSet;
use spitfire\model\query\ResultSetMapping;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\utils\Mixin;

/**
 *
 * @mixin RestrictionGroupBuilder
 */
class QueryBuilder implements QueryBuilderInterface
{
	
	use Mixin;
	
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
	 * @var ResultSetMapping
	 */
	private ?ResultSetMapping $pivot = null;
	
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
		$this->mixin(fn() => new ExtendedRestrictionGroupBuilder($this, $this->query->getRestrictions()));
	}
	
	public function withDefaultMapping() : QueryBuilder
	{
		$copy = clone $this;
		
		/**
		 * We need to select all the fields from the table we're querying to push them into
		 * our model so it can be hydrated.
		 */
		$selected = $copy->query->selectAll();
		$map = new ResultSetMapping($this->model);
		
		foreach ($selected as $select) {
			$map->set($select->getName(), $select->getInput());
		}
		
		$copy->mapping = $map;
		
		return $copy;
	}
	
	public function getMapping() : ResultSetMapping
	{
		return $this->mapping;
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
		$copy->pivot = $mapping;
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
	 * @param callable(ExtendedRestrictionGroupBuilder):void $do
	 * @return QueryBuilder
	 */
	public function group(string $type, callable $do) : QueryBuilder
	{
		$group = $this->query->getRestrictions()->group($type);
		$do(new ExtendedRestrictionGroupBuilder($this, $group));
		return $this;
	}
	
	public function getRestrictions(): RestrictionGroupBuilder
	{
		return new ExtendedRestrictionGroupBuilder($this, $this->query->getRestrictions());
	}
	
	public function restrictions(callable $do): QueryBuilder
	{
		$do($this->getRestrictions());
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
	
	public function where(...$args) : QueryBuilder
	{
		(new ExtendedRestrictionGroupBuilder($this, $this->query->getRestrictions()))->where(...$args);
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
		$result = new ResultSet(
			$this->model->getConnection()->query($this->getQuery()),
			$this->mapping,
			$this->pivot
		);
		$row    = $result->fetch();
		
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
		
		return $this->eagerLoad(new Collection([$row]))->first();
	}
	
	/**
	 *
	 * @return Collection<Model>
	 */
	public function all() : Collection
	{
		/*
		 * Fetch a single row from the database.
		 */
		$result = new ResultSet(
			$this->model->getConnection()->query($this->getQuery()),
			$this->mapping,
			$this->pivot
		);
		
		$rows   = $result->fetchAll();
		
		return $this->eagerLoad($rows);
	}
	
	public function range(int $offset, int $size) : Collection
	{
		/*
		 * Fetch a single row from the database.
		 */
		$query  = clone $this->getQuery();
		$query->range($offset, $size);
		
		
		$result = new ResultSet(
			$this->model->getConnection()->query($query),
			$this->mapping,
			$this->pivot
		);
		
		$rows   = $result->fetchAll();
		return $this->eagerLoad($rows);
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
	 * @param Collection<ActiveRecord> $records
	 */
	protected function eagerLoad(Collection $records) : Collection
	{
		foreach ($this->with as $relation) {
			$meta = $this->model->$relation();
			assert($meta instanceof RelationshipInterface);
			
			$children = $meta->resolveAll($records);
			
			/**
			 * @todo This needs to make use of reflection so it can be used properly.
			 */
			foreach ($records as $record) {
				/**@var $record Record */
				$record->set($relation, $children[$record->getPrimary()]);
			}
		}
		
		return $records;
	}
}
