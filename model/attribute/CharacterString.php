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
	private ?bool $nullable;
	
	private int $length;
	
	public function __construct(int $length = 255, bool $nullable = null)
	{
		$this->nullable = $nullable;
		$this->length = $length;
		
		assert($length > 0);
	}
	
	/**
	 * Get the value of nullable
	 */
	public function isNullable() : ?bool
	{
		return $this->nullable;
	}
	
	public function getLength() : int
	{
		return $this->length;
	}
}
