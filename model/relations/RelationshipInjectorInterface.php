<?php namespace spitfire\model\relations;

use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\RestrictionGroup;

/**
 * A relationship describes how two models connect with each other. This is useful
 * for navigating models and building queries.
 */
interface RelationshipInjectorInterface
{
	
	public function injectWhere(RestrictionGroup $query, Model $model) : void;
	public function injectWhereHas(RestrictionGroup $query, callable $value) : void;
}
