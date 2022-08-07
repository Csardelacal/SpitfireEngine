<?php namespace spitfire\model\query;

use spitfire\collection\Collection;
use spitfire\model\Model;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;

class ResultSet
{
	
	/**
	 *
	 * @var ResultSetMapping
	 */
	private $model;
	
	/**
	 *
	 * @var ResultSetMapping|null
	 */
	private $pivot;
	
	/**
	 * The underlying resultset
	 *
	 * @var ResultInterface
	 */
	private $resultset;
	
	
	public function __construct(ResultInterface $result, ResultSetMapping $model, ResultSetMapping $pivot = null)
	{
		$this->resultset = $result;
		$this->model = $model;
		$this->pivot = $pivot;
	}
	
	public function fetch()
	{
		$raw = $this->resultset->fetchAssociative();
		
		if ($raw === false) {
			return null;
		}
		
		$record = new Record($raw);
		$model = $this->model->make($record);
		
		if ($this->pivot !== null) {
			$pivot = $this->pivot->make($record);
			$model->setPivot($pivot);
		}
		
		return $model;
	}
	
	public function fetchAll() : Collection
	{
		$all = new Collection($this->resultset->fetchAllAssociative());
		
		return $all->each(function (array $raw) : Model {
			$record = new Record($raw);
			$model = $this->model->make($record);
			
		
			if ($this->pivot !== null) {
				$pivot = $this->pivot->make($record);
				$model->setPivot($pivot);
			}
			
			return $model;
		});
	}
}
