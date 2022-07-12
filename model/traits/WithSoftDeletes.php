<?php namespace spitfire\model\traits;

trait WithSoftDeletes
{
	
	private ?int $removed;
	
	public function isDeleted() : bool
	{
		return $this->removed !== null;
	}
	
	public function getDeletionTimestamp() : int
	{
		return $this->removed;
	}
}
