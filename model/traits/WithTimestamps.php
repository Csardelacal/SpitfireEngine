<?php namespace spitfire\model\traits;

trait WithTimestamps
{
	
	private ?int $created;
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
