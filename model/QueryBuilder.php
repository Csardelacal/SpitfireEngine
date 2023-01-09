<?php namespace spitfire\model;

use spitfire\collection\Collection;
use spitfire\model\query\ExtendedRestrictionGroupBuilder;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSet;
use spitfire\model\query\ResultSetMapping;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\QueryOrTableIdentifier;
use spitfire\storage\database\query\RestrictionGroup;
use spitfire\storage\database\query\SelectExpression;
use spitfire\utils\Mixin;

/**
 *
 * @template T of Model
 * @mixin RestrictionGroupBuilder
 */
class QueryBuilder implements QueryBuilderInterface
{
	
	use Mixin;
	
	/**
	 *
	 * @var T
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
	
	/**
	 * 
	 * @param T $model
	 */
	public function __construct(Model $model)
	{
		$this->model = $model;
		$this->query = new DatabaseQuery(new QueryOrTableIdentifier($this->model->getTable()->getTableReference()));
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
	 * @param RestrictionGroup::TYPE_* $type
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
		
		/**
		 * Generate a collection of mappings for this query. If there's a pivot, there
		 * will be several mappings, otherwise it will be just one.
		 * 
		 * @var Collection<ResultSetMapping>
		 */
		$mapping = Collection::fromArray([
			$this->mapping->with($this->with),
			$this->pivot
		])->filter();
			
		/*
		* Fetch a single row from the database.
		*/
		$result = new ResultSet(
			$this->model->getConnection()->query($this->getQuery()),
			$mapping
		);
		
		$row = $result->fetch();
		
		/**
		 * If there is no more rows in the result (alas, there have never been any), the application
		 * should call the or() callable. This can either create a new record, return null or throw
		 * a user defined exception.
		 */
		if ($row === false) {
			return $or === null? null : $or();
		}
		
		/**
		 * 
		 * @var Model
		 */
		$model = $row[0];
		
		if ($this->pivot !== null) {
			$model->setPivot($row[0]);
		}
		
		return $model;
	}
	
	/**
	 *
	 * @return Collection<Model>
	 */
	public function all() : Collection
	{
		/**
		 * Generate a collection of mappings for this query. If there's a pivot, there
		 * will be several mappings, otherwise it will be just one.
		 * 
		 * @var Collection<ResultSetMapping>
		 */
		$mapping = Collection::fromArray([
			$this->mapping->with($this->with),
			$this->pivot
		])->filter();
			
		/*
		* Fetch the records from the database
		*/
		$result = new ResultSet(
			$this->model->getConnection()->query($this->getQuery()),
			$mapping
		);
		
		return $result->fetchAll()->each(function ($row) {
			/**
			 * 
			 * @var Model
			 */
			$model = $row[0];
			
			if ($this->pivot !== null) {
				$model->setPivot($row[0]);
			}
			
			return $model;
		});
	}
	
	public function range(int $offset, int $size) : Collection
	{
		/*
		 * Fetch a single row from the database.
		 */
		$query  = clone $this->getQuery();
		$query->range($offset, $size);
		
		/**
		 * Generate a collection of mappings for this query. If there's a pivot, there
		 * will be several mappings, otherwise it will be just one.
		 */
		$mapping = collect([
			$this->mapping->with($this->with),
			$this->pivot
		])->filter();
			
		/*
		* Fetch the records from the database
		*/
		$result = new ResultSet(
			$this->model->getConnection()->query($query),
			$mapping
		);
		
		return $result->fetchAll()->each(function ($row) {
			/**
			 * 
			 * @var Model
			 */
			$model = $row[0];
			
			if ($this->pivot !== null) {
				$model->setPivot($row[0]);
			}
			
			return $model;
		});
	}
	
	public function count() : int
	{
		$query = $this->query->withoutSelect();
		
		/**
		 * Get the primary index, and make sure that it actually exists.
		 */
		$_primary = $this->getModel()->getTable()->getPrimaryKey();
		assert($_primary !== null);
		assert($_primary->getFields()->count() === 1);
		$primary = $_primary->getFields()->first();
		
		$query->aggregate(
			$this->getQuery()->getFrom()->output()->getOutput($primary->getName()),
			new Aggregate(Aggregate::AGGREGATE_COUNT),
			'c'
		);
		
		
		$result = $this->model->getConnection()->query($query)->fetchOne();
		assert($result !== false);
		
		return $result;
	}
	
	/**
	 * The advantage of counting records like this is that mysql will stop counting
	 * as soon as it found the n records it's supposed to look for.
	 *
	 * @see https://sql-bits.com/check-if-more-than-n-rows-are-returned/
	 */
	public function quickCount(int $upto = 101) : int
	{
		$query = $this->query->withoutSelect();
		
		/**
		 * Get the primary index, and make sure that it actually exists. The primary key also must
		 * have exactly one field.
		 */
		$_primary = $this->getModel()->getTable()->getPrimaryKey();
		assert($_primary !== null);
		assert($_primary->getFields()->count() === 1);
		$primary = $_primary->getFields()->first();
		
		/**
		 * Use the primary key for counting.
		 */
		$query->select($primary->getName());
		$query->range(0, $upto);
		
		/**
		 * Once the inner query is constructed, we wrap it into another query that actually performs
		 * the count. This means that the database server counts and returns only the calculated
		 * result, reducing the traffic between the machines.
		 */
		$outer = new DatabaseQuery(new QueryOrTableIdentifier($query));
		$outer->aggregate(
			$this->getQuery()->getFrom()->output()->getOutput($primary->getName())->removeScope(),
			new Aggregate(Aggregate::AGGREGATE_COUNT),
			'c'
		);
		
		$result = $this->model->getConnection()->query($outer)->fetchOne();
		assert($result !== false);
		
		return $result;
	}
}
