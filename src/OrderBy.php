<?php namespace spitfire\storage\database;

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
	 * @var AggregateFunction
	 */
	private $output;
	
	/**
	 * 
	 * @var string
	 */
	private $direction;
	
	/**
	 * 
	 * @param AggregateFunction $output
	 * @param string $direction
	 */
	public function __construct(AggregateFunction $output, string $direction = OrderBy::ORDER_ASC)
	{	
		$this->output = $output;
		$this->direction = $direction;
	}
	
	/**
	 * 
	 * @return AggregateFunction
	 */
	public function getOutput() : AggregateFunction
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
