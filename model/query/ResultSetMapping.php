<?php namespace spitfire\model\query;

use spitfire\model\Model;
use spitfire\storage\database\identifiers\FieldIdentifier;

class ResultSetMapping
{
	
	private $model;
	
	/**
	 * 
	 * @var FieldIdentifier[]
	 */
	private $map;
	
	public function __construct(Model $model)
	{
		$this->model = $model;
		$this->map = [];
	}
	
	public function getModel()
	{
		return $this->model;
	}
	
	public function map(string $field)
	{
		assert(array_key_exists($field, $this->map));
		return $this->map[$field];
	}
	
	public function set(string $name, FieldIdentifier $field)
	{
		$this->map[$name] = $field;
		return $this;
	}
	
	public function make(array $data) : Model
	{
		
		$body = collect($this->map)->each(function (FieldIdentifier $e) use ($data) : mixed {
			return $data[$e->raw()];
		});
		
		$copy = clone $this->model;
		$copy->hydrate($body);
		
		return $copy;
	}
}
