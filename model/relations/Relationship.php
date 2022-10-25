<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\QueryBuilder;
use spitfire\model\QueryBuilderInterface;
use spitfire\storage\database\Query;

/**
 *
 * @mixin QueryBuilder
 */
abstract class Relationship implements RelationshipInterface, QueryBuilderInterface
{
	
	private $field;
	
	private $referenced;
	
	
	public function __construct(Field $field, Field $referenced)
	{
		$this->field = $field;
		$this->referenced = $referenced;
	}
	
	public function getModel(): Model
	{
		return $this->referenced->getModel();
	}
	
	public function getField() : Field
	{
		return $this->field;
	}
	
	public function localField() : Field
	{
		return $this->field;
	}
	
	public function getReferenced() : Field
	{
		return $this->referenced;
	}
	
	abstract public function startQueryBuilder(): QueryBuilder;
	
	abstract public function injector() : RelationshipInjectorInterface;
	
	# The following functions are used to provide query building. Since PHP won't let us
	# just __call all our interface methods, we need to manually create the mixin.
	# TODO: Maybe a more elegant solution for this would be good
	# TODO: Add docblocks for these methods.
	public function all() : Collection
	{
		return $this->startQueryBuilder()->all();
	}
	
	public function count(): int
	{
		return $this->startQueryBuilder()->count();
	}
	
	public function first(?callable $or = null): ?Model
	{
		return $this->startQueryBuilder()->first($or);
	}
	
	public function getQuery(): Query
	{
		return $this->startQueryBuilder()->getQuery();
	}
	
	public function getRestrictions(): RestrictionGroupBuilder
	{
		return $this->startQueryBuilder()->getRestrictions();
	}
	
	public function group(string $type, callable $do): QueryBuilder
	{
		return $this->startQueryBuilder()->group($type, $do);
	}
	
	public function range(int $offset, int $size): Collection
	{
		return $this->startQueryBuilder()->range($offset, $size);
	}
	
	public function restrictions(callable $do): QueryBuilder
	{
		return $this->startQueryBuilder()->restrictions($do);
	}
	
	public function __call($name, $arguments)
	{
		assert(method_exists($this->startQueryBuilder(), $name));
		return $this->startQueryBuilder()->{$name}(...$arguments);
	}
}
