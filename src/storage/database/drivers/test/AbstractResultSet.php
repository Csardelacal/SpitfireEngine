<?php namespace spitfire\storage\database\drivers\test;

use PDO;
use spitfire\collection\Collection;
use spitfire\storage\database\query\ResultInterface;

class AbstractResultSet implements ResultInterface
{
	
	/**
	 *
	 * @var string[][]
	 */
	private $result;
	
	/**
	 *
	 * @param string[][] $result
	 */
	public function __construct(array $result)
	{
		$this->result = $result;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchNumeric()
	{
		$next = current($this->result);
		next($this->result);
		
		if ($next === false) {
			return false;
		}
		
		assert(is_array($next));
		return array_values($next);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchAssociative()
	{
		$next = current($this->result);
		next($this->result);
		
		if ($next === false) {
			return false;
		}
		
		assert(is_array($next));
		return $next;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchOne()
	{
		$next = current($this->result);
		next($this->result);
		
		if ($next === false) {
			return false;
		}
		
		assert(is_array($next));
		return reset($next);
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchAllNumeric(): array
	{
		$_return = [];
		
		while ($row = current($this->result)) {
			next($this->result);
			$_return[] = array_values($row);
		}
		
		return $_return;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchAllAssociative(): array
	{
		return $this->result;
	}
	
	/**
	 * {@inheritDoc}
	 */
	public function fetchFirstColumn(): array
	{
		$_return = [];
		
		while ($row = current($this->result)) {
			next($this->result);
			$_return[] = reset($row);
		}
		
		return $_return;
	}
	
	public function rowCount(): int
	{
		return count($this->result);
	}
	
	public function columnCount(): int
	{
		$first = reset($this->result);
		
		if ($first === false) {
			return 1;
		}
		
		return count($first);
	}
	
	public function free(): void
	{
		$this->result = [];
	}
}
