<?php namespace spitfire\model\relations;

use spitfire\model\Model;
use spitfire\model\Query;
use spitfire\storage\database\Query as DatabaseQuery;

/**
 * A relationship describes how two models connect with each other. This is useful
 * for navigating models and building queries.
 */
interface RelationshipInjectorInterface
{
	
	public function injectWhere(Query $query, Model $model) : void;
	public function injectWhereHas(DatabaseQuery $query, callable $value) : void;
}
