<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;

/**
 * A relationship describes how two models connect with each other. This is useful
 * for navigating models and building queries.
 */
interface RelationshipInterface
{
	
	public function resolve(ActiveRecord $record) : RelationshipContent;
	
	/**
	 *
	 * @param Collection<ActiveRecord> $records
	 * @return Collection<RelationshipContent>
	 */
	public function resolveAll(Collection $records) : Collection;
	
	public function injector(): RelationshipInjectorInterface;
}
