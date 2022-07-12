<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * The column attribute can be attached to a property of a model, allowing the
 * application to automatically generate fields or columns for the given element.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CharacterString
{
	
	/**
	 *
	 * @var bool
	 */
	private bool $nullable;
	
	private int $length;
	
	public function __construct(bool $nullable = true, int $length = 255)
	{
		$this->nullable = $nullable;
		$this->length = $length;
		
		assert($length > 0);
	}
	
	/**
	 * Get the value of nullable
	 */
	public function isNullable() : bool
	{
		return $this->nullable;
	}
	
	public function getLength() : int
	{
		return $this->length;
	}
}
