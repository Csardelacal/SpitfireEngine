<?php namespace spitfire\model\relations;

use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\Query;

/**
 *
 * @mixin Query
 */
abstract class Relationship implements RelationshipInterface
{
	
	private $field;
	
	private $referenced;
	
	
	public function __construct(Field $field, Field $referenced)
	{
		$this->field = $field;
		$this->referenced = $referenced;
	}
	
	public function getModel(): Model
	{
		return $this->referenced->getModel();
	}
	
	public function getField() : Field
	{
		return $this->field;
	}
	
	public function getReferenced() : Field
	{
		return $this->referenced;
	}
	
	public function getQuery(): Query
	{
		$query = $this->referenced->getModel()->query();
		
		$query->getQuery()->where(
			$query->getQuery()->getFrom()->output()->getOutput($this->referenced->getField()),
			$this->field->getModel()->getPrimaryData()
		);
		
		return $query;
	}
	
	public function injector() : RelationshipInjectorInterface
	{
		return new DirectRelationshipInjector($this->field, $this->referenced);
	}
	
	public function __call($name, $arguments)
	{
		assert(method_exists($this->getQuery(), $name));
		return $this->getQuery()->{$name}(...$arguments);
	}
}
