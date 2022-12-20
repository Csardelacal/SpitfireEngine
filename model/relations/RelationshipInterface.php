<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\Field;
use spitfire\model\Model;

/**
 * A relationship describes how two models connect with each other. This is useful
 * for navigating models and building queries.
 * 
 * @template LOCAL of Model
 * @template REMOTE of Model
 */
interface RelationshipInterface
{
	
	/**
	 * 
	 * @return Field<LOCAL>
	 */
	public function localField() : Field;
	
	public function resolve(ActiveRecord $record) : RelationshipContent;
	
	/**
	 *
	 * @param Collection<ActiveRecord> $records
	 * @return Collection<RelationshipContent>
	 */
	public function resolveAll(Collection $records) : Collection;
	
	/**
	 * 
	 * @return RelationshipInjectorInterface<REMOTE>
	 */
	public function injector(): RelationshipInjectorInterface;
}
