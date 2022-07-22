<?php namespace spitfire\storage\database\diff;

use spitfire\collection\Collection;
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
	 * @var Collection<Field>
	 */
	private Collection $fields;
	
	/**
	 *
	 * @var Collection<IndexInterface>
	 */
	private Collection $indexes;
	
	public function __construct()
	{
		$this->fields = new Collection();
		$this->indexes = new Collection();
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
	 * @return Collection<Field>
	 */
	public function getFields() : Collection
	{
		return $this->fields;
	}
	
	/**
	 *
	 * @return Collection<IndexInterface>
	 */
	public function getIndexes() : Collection
	{
		return $this->indexes;
	}
}
