<?php namespace spitfire\model\query;

use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\Model;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;

class ResultSet
{
	
	private ResultSetMapping $map;
	
	/**
	 * The underlying resultset
	 *
	 * @var ResultInterface
	 */
	private ResultInterface $resultset;
	
	
	public function __construct(ResultInterface $result, ResultSetMapping $map)
	{
		$this->resultset = $result;
		$this->map = $map;
	}
	
	public function fetch() : Model|false
	{
		$assoc = $this->resultset->fetchAssociative();
		return $assoc === false? false : $this->map->makeOne(new Record($assoc));
	}
	
	public function fetchAll() : Collection
	{
		$all = new Collection($this->resultset->fetchAllAssociative());
		return $this->map->make($all);
	}
}
