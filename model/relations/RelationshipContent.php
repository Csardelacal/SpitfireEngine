<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\Model;

class RelationshipContent
{
	
	/**
	 * 
	 * @param bool $single
	 * @param Collection<Model> $payload
	 */
	public function __construct(
		private bool $single,
		private Collection $payload
	) {
	}
	
	
	/**
	 * 
	 * @return bool
	 */
	public function isSingle() : bool
	{
		return $this->single;
	}
	
	/**
	 * 
	 * @return Collection<Model>
	 */
	public function getPayload() : Collection
	{
		return $this->payload;
	}
}
