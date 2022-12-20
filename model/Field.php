<?php namespace spitfire\model;

use spitfire\model\Model;

/**
 * Represents a table's field in a database. Contains information about the
 * table the field belongs to, the name of the field and if it is (or not) a
 * primary key or auto-increment field.
 *
 * @template T of Model
 * @author César de la Cal <cesar@magic3w.com>
 */
class Field
{
	
	/**
	 * 
	 * @var T
	 */
	private Model $model;
	private string $name;
	
	/**
	 * 
	 * @param T $model
	 * @param string $field
	 */
	public function __construct(Model $model, string $field)
	{
		$this->model = $model;
		$this->name = $field;
	}
	
	/**
	 *
	 * @return T
	 */
	public function getModel() : Model
	{
		return $this->model;
	}
	
	public function getName()
	{
		return $this->name;
	}
	
	/**
	 *
	 * @deprecated
	 * @see Field::getName
	 */
	public function getField()
	{
		return $this->name;
	}
	
	public function getValue()
	{
		return $this->model->get($this->name);
	}
}
