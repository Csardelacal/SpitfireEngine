<?php namespace spitfire\validation\rules;

use Closure;
use spitfire\validation\ValidationError;

/**
 * This rule tests values against a provided function that checks whether the 
 * data it will receive later is valid.
 * 
 * @author César de la Cal <cesar@magic3w.com>
 */
class ClosureValidationRule extends BaseRule
{
	
	/**
	 * The function used to evaluate the value. If this function returns true, the
	 * application may continue, otherwise the data it received was invalid.
	 * 
	 * @var Closure
	 */
	private $closure;
	
	/**
	 * Creates a rule to test a value against a closure. If the closure returns
	 * true, the test is considered succesful and the application can continue.
	 * 
	 * @param Closure $closure
	 * @param string $message
	 * @param string $extendedMessage
	 */
	public function __construct(Closure $closure, $message, $extendedMessage = '') 
	{
		$this->closure = $closure;
		parent::__construct($message, $extendedMessage);
	}
	
	
	/**
	 * Tests a value with this validation rule. Returns the errors detected for
	 * this element or boolean false on no errors.
	 * 
	 * @param mixed $value
	 * @return ValidationError|boolean
	 */
	public function test($value) 
	{
		if (!(($this->closure)($value))) {
			return new ValidationError($this->getMessage(), $this->getExtendedMessage());
		}
		
		return false;
	}

}