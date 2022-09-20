<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * The column attribute can be attached to a property of a model, allowing the
 * application to automatically generate fields or columns for the given element.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Text
{
	
	private ?bool $nullable;
	
	public function __construct(bool $nullable = null)
	{
		$this->nullable = $nullable;
	}
	
	/**
	 * Get the value of nullable
	 */
	public function isNullable() : ?bool
	{
		return $this->nullable;
	}
}
