<?php namespace spitfire\storage\database\diff;

use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\storage\database\Field;
use spitfire\storage\database\IndexInterface;

/**
 * ToBeAdded is just a very syntactical way of describing a partial layout,
 * they contain similar information to a layout, but they do not have to
 * add up to a complete layout.
 */
class ToBeAdded
{
	
	/**
	 *
	 * @var TypedCollection<Field>
	 */
	private TypedCollection $fields;
	
	/**
	 *
	 * @var TypedCollection<IndexInterface>
	 */
	private TypedCollection $indexes;
	
	public function __construct()
	{
		$this->fields = new TypedCollection(Field::class);
		$this->indexes = new TypedCollection(IndexInterface::class);
	}
	
	public function addField(Field $field) : ToBeAdded
	{
		$this->fields->push($field);
		return $this;
	}
	
	public function addIndex(IndexInterface $index) : ToBeAdded
	{
		$this->indexes->push($index);
		return $this;
	}
	
	/**
	 *
	 * @return TypedCollection<Field>
	 */
	public function getFields() : TypedCollection
	{
		return $this->fields;
	}
	
	/**
	 *
	 * @return TypedCollection<IndexInterface>
	 */
	public function getIndexes() : TypedCollection
	{
		return $this->indexes;
	}
}
