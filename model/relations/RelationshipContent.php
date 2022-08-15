<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;

class RelationshipContent
{
	
	public function __construct(
		private bool $single,
		private Collection $payload
	) {
	}
	
	public function isSingle() : bool
	{
		return $this->single;
	}
	
	public function getPayload() : Collection
	{
		return $this->payload;
	}
}
