<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\Model;
use spitfire\model\SurrogateRecord;

/**
 * A relationship describes how two models connect with each other. This is useful
 * for navigating models and building queries.
 */
interface RelationshipInterface
{
	
	public function resolve(SurrogateRecord $record) : RelationshipContent;
	
	/**
	 *
	 * @param Collection<SurrogateRecord> $records
	 * @return Collection<RelationshipContent>
	 */
	public function resolveAll(Collection $records) : Collection;
	
	/**
	 * Eagerly load the children of a relationship. Please note that this receives a collection of
	 * parents and returns a collection grouped by their ID.
	 *
	 * @deprecated
	 * @param Collection<Model> $parents
	 * @return Collection<Collection<Model>>
	 */
	public function eagerLoad(Collection $parents) : Collection;
	
	public function injector(): RelationshipInjectorInterface;
}
