<?php namespace spitfire\storage\database;

use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\PrivateException;

/**
 * A restriction indicates a condition a record in a database's relation must 
 * satisfy to be returned by a database query.
 * 
 * Restrictions can only contain basic data-types like integers, floats, strings 
 * or enums as their value.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Restriction
{
	
	/**
	 * The field that this restriction is searching on. This lets the application
	 * know which table, field and alias to use to refer to when assembling a query.
	 *
	 * @var QueryField
	 */
	private $field;
	
	/**
	 * The value can be any value that our database can accept within the field. Please note
	 * that during runtime the system does not check whether this data is clean.
	 * 
	 * @var mixed
	 */
	private $value;
	
	/**
	 * The operator used to represent the type of restriction within the database field. These
	 * are generally greather than, smaller than and equals.
	 * 
	 * @var string
	 */
	private $operator;
	
	const LIKE_OPERATOR  = 'LIKE';
	const EQUAL_OPERATOR = '=';
	
	/**
	 * Instances a new restriction.
	 * 
	 * @param QueryField $field
	 * @param mixed $value
	 * @param string $operator
	 */
	public function __construct(QueryField $field, $value, $operator = self::EQUAL_OPERATOR) 
	{
		$this->field    = $field;
		$this->value    = $value;
		$this->operator = trim($operator);
	}
	
	/**
	 * Returns the field we're querying for the value of the restriction.
	 * 
	 * @return QueryField
	 */
	public function getField() : QueryField
	{
		return $this->field;
	}
	
	public function getOperator() : string
	{
		if (is_array($this->value) && $this->operator != 'IN' && $this->operator != 'NOT IN') {
			return 'IN';
		}
		
		return $this->operator;
	}

	/**
	 * Returns the value we're searching the database for.
	 * 
	 * @return string
	 */
	public function getValue() 
	{
		return $this->value;
	}
	
	/**
	 * Negates the operator and returns the operation this leads to.
	 * 
	 * @return string
	 */
	public function negate() : string
	{
		switch ($this->operator) {
			case '=': 
				return $this->operator = '<>';
			case '<>': 
				return $this->operator = '=';
			case '>': 
				return $this->operator = '<';
			case '<': 
				return $this->operator = '>';
			case 'IS': 
				return $this->operator = 'IS NOT';
			case 'IS NOT': 
				return $this->operator = 'IS';
			case 'LIKE': 
				return $this->operator = 'NOT LIKE';
			case 'NOT LIKE': 
				return $this->operator = 'LIKE';
		}
		
		throw new ApplicationException('Invalid operator detected', 2108191755);
	}
}
