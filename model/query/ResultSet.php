<?php namespace spitfire\model\query;

use spitfire\collection\Collection;
use spitfire\model\Model;
use spitfire\storage\database\ResultSetInterface;

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
	 * @var ResultSetInterface
	 */
	private $resultset;
	
	
	public function __construct(ResultSetInterface $result, ResultSetMapping $model, ResultSetMapping $pivot = null)
	{
		$this->resultset = $result;
		$this->model = $model;
		$this->pivot = $pivot;
	}
	
	public function fetch()
	{
		$raw = $this->resultset->fetch();
		$model = $this->model->make($raw);
		$pivot = $this->pivot? $this->pivot->make($raw) : null;
		
		$model->setPivot($pivot);
		return $model;
	}
	
	public function fetchAll() : Collection
	{
		$all = $this->resultset->fetchAll();
		
		return $all->each(function (array $raw) : Model {
			$model = $this->model->make($raw);
			$pivot = $this->pivot? $this->pivot->make($raw) : null;
			
			$model->setPivot($pivot);
			return $model;
		});
	}
}
