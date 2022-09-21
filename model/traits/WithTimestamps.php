<?php namespace spitfire\model\traits;

use spitfire\model\attribute\Integer;

trait WithTimestamps
{
	
	#[Integer(true, false)]
	private ?int $created;
	
	#[Integer(true, true)]
	private ?int $updated;
	
	public function getCreationTimestamp() :? int
	{
		return $this->created;
	}
	
	public function getUpdateTimestamp() :? int
	{
		return $this->updated;
	}
}
