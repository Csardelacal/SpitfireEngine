<?php namespace spitfire\model\traits;

use spitfire\model\attribute\LongInteger;
use spitfire\model\attribute\Primary;

trait WithId
{
	
	#[LongInteger(true)]
	#[Primary]
	private ?int $_id;
	
	public function getId() :? int
	{
		return $this->_id;
	}
}
