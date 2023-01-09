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
 */
class ResultSet
{
	
	/**
	 * 
	 * @var Collection<ResultSetMapping>
	 */
	private Collection $maps;
	
	/**
	 * The underlying resultset
	 *
	 * @var ResultInterface
	 */
	private ResultInterface $resultset;
	
	/**
	 * 
	 * @param ResultInterface $result
	 * @param Collection<ResultSetMapping> $map
	 */
	public function __construct(ResultInterface $result, Collection $map)
	{
		$this->resultset = $result;
		$this->maps = $map;
	}
	
	/**
	 * 
	 * @return Collection<Model>|false
	 */
	public function fetch() : Collection|false
	{
		$assoc = $this->resultset->fetchAssociative();
		
		if ($assoc === false) {
			return false;
		}
		
		return $this->maps->each(fn(ResultSetMapping $e) => $e->makeOne(new Record($assoc)));
	}
	
	/**
	 * 
	 * @return Collection<Collection<Model>>
	 */
	public function fetchAll() : Collection
	{
		$all = Collection::fromArray($this->resultset->fetchAllAssociative());
		return $this->maps->each(fn(ResultSetMapping $e) => $e->make($all));
	}
}
