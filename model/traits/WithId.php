<?php namespace spitfire\model\traits;

use spitfire\model\attribute\Integer;
use spitfire\model\attribute\Primary;

trait WithId
{
	
	#[Integer(true)]
	#[Primary]
	private ?int $_id;
	
	public function getId() :? int
	{
		return $this->_id;
	}
}
