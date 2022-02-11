<?php namespace spitfire\storage\database\drivers\internal;

use spitfire\collection\Collection;
use spitfire\storage\database\ResultSetInterface;

class MockResultSet implements ResultSetInterface
{
	
	/**
	 * 
	 * @return void[]
	 */
	public function fetch() : array
	{
		return [];
	}
	
	public function fetchAll() : Collection
	{
		return new Collection();
	}
}
