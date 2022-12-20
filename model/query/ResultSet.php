<?php namespace spitfire\model\query;

use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\Model;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;

/**
 * 
 * @todo If we're going to have pivots, it may be better to have them here than
 * in the ResultSetMapping (making the mapping recusrive)
 * 
 * @template T of Model
 */
class ResultSet
{
	
	/**
	 * 
	 * @var ResultSetMapping<T,null>
	 */
	private ResultSetMapping $map;
	
	/**
	 * The underlying resultset
	 *
	 * @var ResultInterface
	 */
	private ResultInterface $resultset;
	
	/**
	 * 
	 * @param ResultInterface $result
	 * @param ResultSetMapping<T,null> $map
	 */
	public function __construct(ResultInterface $result, ResultSetMapping $map)
	{
		$this->resultset = $result;
		$this->map = $map;
	}
	
	/**
	 * 
	 * @return T|false
	 */
	public function fetch() : Model|false
	{
		$assoc = $this->resultset->fetchAssociative();
		return $assoc === false? false : $this->map->makeOne(new Record($assoc));
	}
	
	/**
	 * 
	 * @return Collection<T>
	 */
	public function fetchAll() : Collection
	{
		$all = new Collection($this->resultset->fetchAllAssociative());
		return $this->map->make($all);
	}
}
