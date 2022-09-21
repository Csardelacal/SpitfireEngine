<?php namespace spitfire\model\traits;

use spitfire\model\attribute\Integer;

trait WithSoftDeletes
{
	
	#[Integer(true, true)]
	private ?int $removed;
	
	public function isDeleted() : bool
	{
		return $this->removed !== null;
	}
	
	public function getDeletionTimestamp() :? int
	{
		return $this->removed;
	}
}
