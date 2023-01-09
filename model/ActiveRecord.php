<?php namespace spitfire\model;

use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\exceptions\ApplicationException;
use spitfire\model\relations\BelongsToOne;
use spitfire\model\relations\RelationshipContent;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\Record;
use spitfire\utils\Mixin;

/**
 * The surrogate acts as a bridge between model and record, it implements lazy
 * loading whenever needed and manages the conversion between raw data in the
 * model and the physical data in the Record.
 *
 * @todo Add a cache that maintains the data that the surrogate contains.
 *
 * @method bool has(string $key)
 * @method string[] raw()
 * @method string[] keys()
 *
 * @mixin Record
 */
class ActiveRecord
{
	
	use Mixin;
	
	/**
	 * The model provides us with information about the relationships
	 *
	 * @var Model
	 */
	private Model $model;
	
	/**
	 * The record contains the data we need to work with.
	 *
	 * @var Record
	 */
	private Record $record;
	
	/**
	 *
	 * @var Collection<RelationshipContent>
	 */
	private Collection $cache;
	
	public function __construct(Model $model, Record $record)
	{
		$this->model = $model;
		$this->record = $record;
		$this->cache  = new TypedCollection(RelationshipContent::class);
		$this->mixin($record);
	}
	
	/**
	 * Returns the current data for the provided field. During development, with assertions,
	 * this method will fail when attempting to read a non-existing field.
	 *
	 * @param string $field
	 * @return int|float|string|null|RelationshipContent
	 */
	public function get(string $field)
	{
		/**
		 * If the model has a relationship for this field, we will proceed to lazy load the model.
		 */
		if (method_exists($this->model, $field)) {
			return $this->lazy($field);
		}
		
		return $this->record->get($field)?? null;
	}
	
	/**
	 * The value of the primary key, null means that the software expects the
	 * database to assign this record a primary key on insert.
	 *
	 * When editing the primary key value this will ALWAYS return the data that
	 * the system assumes to be in the database.
	 *
	 * @return int|float|string
	 */
	public function getPrimary()
	{
		$fields = $this->model->getTable()->getPrimaryKey()->getFields();
		
		if ($fields->isEmpty()) {
			throw new ApplicationException('Record has no primary key', 2101181306);
		}
		
		return $this->record->get($fields[0]->getName());
	}
	
	
	/**
	 * Sets a field to a given value.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return ActiveRecord
	 */
	public function set(string $field, $value) : ActiveRecord
	{
		assert($this->has($field), sprintf('Record does not have expected field %s', $field));
		
		if ($this->cache->has($field)) {
			unset($this->cache[$field]);
		}
		
		if (!($value instanceof RelationshipContent)) {
			$this->record->set($field, $value);
			return $this;
		}
		
		/**
		 * If the data can be cached, we cache it. This prevents database roundtrips.
		 * The only data being cached is relationship related.
		 */
		$this->cache[$field] = $value;
		
		if ($value->isSingle()) {
			$this->record->set($field, $value->getPayload()->first()?->getPrimary());
		}
		
		return $this;
	}
	
	/**
	 * Lazy load the data for a field.
	 */
	public function lazy(string $field) : RelationshipContent
	{
		$relationship = $this->model->$field();
		assert($relationship instanceof RelationshipInterface);
		
		if ($this->cache->has($field)) {
			return $this->cache[$field];
		}
		
		/**
		 * If the relationship does not contain any data, and it's a belongstoone relation
		 * we skip loading the data since we know there is none.
		 *
		 * @todo Move this to the appropriate relationship
		 */
		if ($relationship instanceof BelongsToOne && $this->record->get($field) === null) {
			return new RelationshipContent(true, new TypedCollection(Model::class));
		}
		
		/**
		 * Request the method to find the data we need for the relationship to be resolved
		 *
		 */
		return $relationship->resolve($this);
	}
	
	public function getUnderlyingRecord() : Record
	{
		return $this->record;
	}
	
	public function getModel() : Model
	{
		return $this->model;
	}
}
