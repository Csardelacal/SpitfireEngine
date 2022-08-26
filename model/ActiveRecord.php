<?php namespace spitfire\model;

use spitfire\collection\Collection;
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
	
	public function __construct(Model $model, Record $record)
	{
		$this->model = $model;
		$this->record = $record;
		$this->mixin($record);
	}
	
	/**
	 * Returns the current data for the provided field. During development, with assertions,
	 * this method will fail when attempting to read a non-existing field.
	 *
	 * @param string $field
	 * @return mixed
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
	 * Sets a field to a given value.
	 *
	 * @param string $field
	 * @param mixed $value
	 * @return ActiveRecord
	 */
	public function set(string $field, $value) : ActiveRecord
	{
		assert($this->has($field));
		
		if ($value instanceof Model) {
			$value = $value->getPrimary();
		}
		
		$this->record->set($field, $value);
		return $this;
	}
	
	/**
	 * Lazy load the data for a field.
	 */
	public function lazy(string $field) : RelationshipContent
	{
		$relationship = $this->model->$field();
		assert($relationship instanceof RelationshipInterface);
		
		/**
		 * If the relationship does not contain any data, and it's a belongstoone relation
		 * we skip loading the data since we know there is none.
		 *
		 * @todo Move this to the appropriate relationship
		 */
		if ($relationship instanceof BelongsToOne && $this->record->get($field) === null) {
			return new RelationshipContent(true, new Collection());
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