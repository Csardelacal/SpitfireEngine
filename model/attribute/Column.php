<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * The column attribute can be attached to a property of a model, allowing the
 * application to automatically generate fields or columns for the given element.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Column
{
	
	/**
	 *
	 * @var string
	 */
	private string $type;
	
	/**
	 *
	 * @var bool
	 */
	private bool $nullable;
	
	/**
	 *
	 * @var bool
	 */
	private bool $autoincrement;
	
	public function __construct(string $type, bool $nullable = true, bool $autoincrement = false)
	{
		$this->type = $type;
		$this->nullable = $nullable;
		$this->autoincrement = $autoincrement;
	}
	
	/**
	 * Get the value of type
	 *
	 * @return  string
	 */
	public function getType() : string
	{
		return $this->type;
	}
	
	/**
	 * Get the value of nullable
	 */
	public function isNullable() : string
	{
		return $this->nullable;
	}
	
	/**
	 * Get the value of autoincrement
	 */
	public function isAutoincrement() : string
	{
		return $this->autoincrement;
	}
}
