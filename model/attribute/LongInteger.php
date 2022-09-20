<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * The column attribute can be attached to a property of a model, allowing the
 * application to automatically generate fields or columns for the given element.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class LongInteger
{
	
	/**
	 *
	 * @var bool
	 */
	private bool $unsigned;
	
	/**
	 * The original idea for this field was to make it automatic, based on whether the
	 * model itself accepts null values. This has an issue though, a model can be in an
	 * unsaved state - which means that a value that the DBMS does reject as null needs
	 * to be null on our side while it's unsaved.
	 * 
	 * To handle this, I introduced the override here. It allows you to explicitly set the
	 * field to be nullable (or not) when needed but will default to the model behavior
	 * if unset.
	 */
	private ?bool $nullable;
	
	public function __construct(bool $unsigned = false, bool $nullable = null)
	{
		$this->unsigned = $unsigned;
		$this->nullable = $nullable;
	}
	
	/**
	 * Get the value of unsigned
	 */
	public function isUnsigned() : bool
	{
		return $this->unsigned;
	}
	
	/**
	 * Get the value of nullable
	 */
	public function isNullable() :? bool
	{
		return $this->nullable;
	}
}
