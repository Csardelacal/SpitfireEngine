<?php namespace spitfire\model\traits;

trait WithId
{
	
	private int $_id;
	
	public function getId() : int
	{
		return $this->_id;
	}
}
