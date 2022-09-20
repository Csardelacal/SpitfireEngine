<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * The column attribute can be attached to a property of a model, allowing the
 * application to automatically generate fields or columns for the given element.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Integer
{
	
	/**
	 *
	 * @var bool
	 */
	private bool $unsigned;
	
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
