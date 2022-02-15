<?php namespace spitfire\model\relations;

use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\model\query\Queriable;

abstract class Relationship implements RelationshipInterface
{
	
	use Queriable;
	
	private $field;
	
	private $referenced;
	
	
	public function __construct(Field $field, Field $referenced)
	{
		$this->field = $field;
		$this->referenced = $referenced;
	}
	
	public function getQuery() : Query
	{
		$query = $this->referenced->getModel()->query();
		
		$query->where(
			$this->referenced->getField(),
			$this->field->getValue()
		);
		
		return $query;
	}
	
	public function getField() : Field
	{
		return $this->field;
	}
	
	public function getReferenced() : Field
	{
		return $this->referenced;
	}
	
	public function getReferencedModel() : Model
	{
		return $this->referenced->getModel();
	}
}
