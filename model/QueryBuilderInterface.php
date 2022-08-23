<?php namespace spitfire\model;

use spitfire\collection\Collection;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSet;
use spitfire\model\query\ResultSetMapping;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\utils\Mixin;

/**
 *
 * @mixin RestrictionGroupBuilder
 */
interface QueryBuilderInterface
{
	
	public function getQuery() : DatabaseQuery;
	
	public function getModel() : Model;
	
	/**
	 * Provides access to the restrictions that are applied to this query in particular.
	 * 
	 * @return RestrictionGroupBuilder
	 */
	public function getRestrictions() : RestrictionGroupBuilder;
	
	/**
	 * Provides fluent access to the restrictions that the query holds. This allows the
	 * application to seamlessly chain methods manipulating the restrictions and other
	 * components of the query.
	 *
	 * @param callable(RestrictionGroupBuilder):void $do
	 * @return QueryBuilder
	 */
	public function restrictions(callable $do) : QueryBuilder;
	
	/**
	 *
	 * @param string $type
	 * @param callable(RestrictionGroupBuilder):void $do
	 * @return QueryBuilder
	 */
	public function group(string $type, callable $do) : QueryBuilder;
	
	/**
	 *
	 * @param callable():Model|null $or This function can either: return null, return a model
	 * or throw an exception
	 * @return Model|null
	 */
	public function first(callable $or = null):? Model;
	
	/**
	 *
	 * @return Collection<Model>
	 */
	public function all() : Collection;
	
	public function range(int $offset, int $size) : Collection;
	
	public function count() : int;
}
