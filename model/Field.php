<?php namespace spitfire\model;

use spitfire\model\Model;
use spitfire\model\Schema;
use spitfire\storage\database\Table;
use spitfire\validation\ValidationError;

/**
 * Represents a table's field in a database. Contains information about the
 * table the field belongs to, the name of the field and if it is (or not) a
 * primary key or auto-increment field.
 *
 * @author César de la Cal <cesar@magic3w.com>
 */
class Field
{
	
	private $model;
	private $name;
	
	public function __construct($model, $field)
	{
		$this->model = $model;
		$this->name = $field;
	}
	
	/**
	 * 
	 * @return Model
	 */
	public function getModel()
	{
		return $this->model;
	}
	
	public function getField()
	{
		return $this->name;
	}
	
	public function getValue()
	{
		return $this->model->get($this->name);
	}
}
