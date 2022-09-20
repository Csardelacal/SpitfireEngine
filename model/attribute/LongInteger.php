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
	
	public function __construct(bool $unsigned = false)
	{
		$this->unsigned = $unsigned;
	}
	
	/**
	 * Get the value of unsigned
	 */
	public function isUnsigned() : bool
	{
		return $this->unsigned;
	}
}
