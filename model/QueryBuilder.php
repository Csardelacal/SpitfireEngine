<?php namespace spitfire\model;

use ReflectionClass;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException as PrivateApplicationException;
use spitfire\exceptions\user\ApplicationException;
use spitfire\model\query\Queriable;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSetMapping;
use spitfire\model\relations\Relationship;
use spitfire\model\relations\RelationshipMultipleInterface;
use spitfire\model\relations\RelationshipSingleInterface;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\identifiers\FieldIdentifier;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\Record;
use spitfire\utils\Strings;

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
		$table = $this->query->getFrom()->output();
		
		/**
		 * Extract the name of the fields so we can assign it back to the generic mapping
		 * that will read the data from the query into the model.
		 *
		 * @var Collection<FieldIdentifier>
		 */
		$fields = $table->getOutputs();
		
		/**
		 *
		 * @var string[]
		 */
		$names  = $fields->each(function (FieldIdentifier $e) : string {
			$raw = $e->raw();
			return Strings::underscores2camel(array_pop($raw));
		})->toArray();
		
		$this->mapping = new ResultSetMapping($this->model, array_combine($names, $fields->toArray()));
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
			if (is_string($or)) {
				throw new $or('No records found');
			}
			if (is_callable($or)) {
				return $or();
			}
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
	
	protected function eagerLoad(Collection $result) : Collection
	{
		$reflection = new ReflectionClass($this->model);
		
		foreach ($this->with as $relation) {
			$relationship = $this->model->$relation();
			
			/**
			 * The relationship we retrieved must obviously be a relationship, it must also
			 * provide a proper value in the model so we can write to it.
			 */
			assert($relationship instanceof Relationship);
			assert($reflection->hasProperty($relationship->getField()->getField()));
			
			/**
			 * We need to reflect the property of the model so we can write to it
			 * directly without dispatching any get or set magic methods. This allows
			 * spitfire to populate the model without triggering user validation or
			 * similar.
			 */
			$_property = $reflection->getProperty($relationship->getField()->getField());
			$_property->setAccessible(true);
			
			/**
			 * Fetch the collection of children that are related to the resultset
			 * we retrieved.
			 */
			$children = $relationship->eagerLoad($result);
			$field = $relationship->getField();
			
			foreach ($result as $record) {
				/**
				 * If the relationship provides multiple results, the application needs to expect
				 * a collection in the field that the relationship provides.
				 *
				 * The collection is cloned, so that changes to one record do not propagate to other
				 * records.
				 */
				if ($relationship instanceof RelationshipMultipleInterface) {
					$payload = $children[$field->getField()];
					
					/**
					 * Verify that the payload we received from the database is actually what we need and
					 * not something else.
					 */
					assert($payload instanceof Collection);
					assert($payload->containsOnly(Model::class));
					
					$_property->setValue($record, clone $children[$field->getField()]);
				}
				/**
				 * Otherwise, if the application expects a single model, then the data should be a
				 * model.
				 */
				elseif ($relationship instanceof RelationshipSingleInterface) {
					$payload = $children[$field->getField()]->first();
					
					/**
					 * Make sure that the payload we received is either null or a model. No other payload
					 * can be accepted.
					 */
					assert($payload instanceof Model || $payload === null);
					$_property->setValue($record, $payload);
				}
				else {
					throw new PrivateApplicationException('Invalid relationship type');
				}
			}
			
			/**
			 * We lock the property back up, so that no component may accidentally write to it when
			 * not allowed.
			 */
			$_property->setAccessible(false);
		}
		
		return $result;
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
