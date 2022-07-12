<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * The column attribute can be attached to a property of a model, allowing the
 * application to automatically generate fields or columns for the given element.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class EnumType
{
	
	/**
	 *
	 * @var bool
	 */
	private bool $nullable;
	
	private array $options;
	
	public function __construct(array $options, bool $nullable = true)
	{
		$this->nullable = $nullable;
		$this->options = $options;
		
		assert(array_reduce($options, function (bool $carry, string $next) {
			return $carry && !empty($next);
		}, true));
	}
	
	/**
	 * Get the value of nullable
	 */
	public function isNullable() : bool
	{
		return $this->nullable;
	}
	
	public function getOptions() : array
	{
		return $this->options;
	}
}
