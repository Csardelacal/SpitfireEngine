<?php namespace spitfire\model\relations;

use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\QueryBuilder;

/**
 *
 * @mixin QueryBuilder
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
	
	abstract public function getQuery(): QueryBuilder;
	
	abstract public function injector() : RelationshipInjectorInterface;
	
	public function __call($name, $arguments)
	{
		assert(method_exists($this->getQuery(), $name));
		return $this->getQuery()->{$name}(...$arguments);
	}
}
