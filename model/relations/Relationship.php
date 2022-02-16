<?php namespace spitfire\model\relations;

use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\model\query\Queriable;
use spitfire\storage\database\Query as DatabaseQuery;

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
	
	public function getQuery() : DatabaseQuery
	{
		$query = $this->referenced->getModel()->query();
		
		$query->where(
			$this->referenced->getField(),
			$this->field->getValue()
		);
		
		return $query->getQuery();
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
	
	public function getReferencedModel() : Model
	{
		return $this->referenced->getModel();
	}
}
