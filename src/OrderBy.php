<?php namespace spitfire\storage\database;

use spitfire\storage\database\query\OutputObjectInterface;

/**
 * The order by objects allow queries to indicate by which means they should be 
 * sorted, allowing the application to sort a results set it would retrieve from
 * a database.
 */
class OrderBy
{
	
	const ORDER_ASC = 'ASC';
	const ORDER_DESC = 'DESC';
	
	/**
	 * 
	 * @var FieldReference
	 */
	private $output;
	
	/**
	 * 
	 * @var string
	 */
	private $direction;
	
	/**
	 * 
	 * @param FieldReference $output
	 * @param string $direction
	 */
	public function __construct(FieldReference $output, string $direction = OrderBy::ORDER_ASC)
	{	
		$this->output = $output;
		$this->direction = $direction;
	}
	
	/**
	 * 
	 * @return FieldReference
	 */
	public function getOutput() : FieldReference
	{
		return $this->output;
	}
	
	/**
	 * Returns the direction in which the result set is to be sorted.
	 * 
	 * @return string
	 */
	public function getDirection() : string
	{
		return $this->direction;
	}
	
}
