<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\QueryBuilder;
use spitfire\model\QueryBuilderInterface;
use spitfire\storage\database\Query;
use spitfire\utils\Mixin;

/**
 *
 * @template LOCAL of Model
 * @template REMOTE of Model
 * @implements RelationshipInterface<LOCAL,REMOTE>
 * @mixin QueryBuilder<REMOTE>
 */
abstract class Relationship implements RelationshipInterface, QueryBuilderInterface
{
	
	use Mixin;
	
	/**
	 * 
	 * @var Field<LOCAL>
	 */
	private Field $field;
	
	/**
	 * 
	 * @var Field<REMOTE>
	 */
	private Field $referenced;
	
	/**
	 * 
	 * @param Field<LOCAL> $field
	 * @param Field<REMOTE> $referenced
	 * 
	 */
	public function __construct(Field $field, Field $referenced)
	{
		$this->field = $field;
		$this->referenced = $referenced;
		
		/**
		 * If this object receives a function call that it cannot handle, forward
		 * it to the querybuilder.
		 */
		$this->mixin(fn() => $this->startQueryBuilder());
	}
	
	public function getModel(): Model
	{
		return $this->referenced->getModel();
	}
	
	/**
	 * 
	 * @return Field<LOCAL>
	 */
	public function getField() : Field
	{
		return $this->field;
	}
	
	/**
	 * 
	 * @return Field<LOCAL>
	 */
	public function localField() : Field
	{
		return $this->field;
	}
	
	/**
	 * 
	 * @return Field<REMOTE>
	 */
	public function getReferenced() : Field
	{
		return $this->referenced;
	}
	
	/**
	 * 
	 * @return QueryBuilder<REMOTE>
	 */
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
	
	/**
	 * 
	 * @return QueryBuilder<REMOTE>
	 */
	public function group(string $type, callable $do): QueryBuilder
	{
		return $this->startQueryBuilder()->group($type, $do);
	}
	
	/**
	 * 
	 * @return Collection<REMOTE>
	 */
	public function range(int $offset, int $size): Collection
	{
		return $this->startQueryBuilder()->range($offset, $size);
	}
	
	/**
	 * 
	 * @return QueryBuilder<REMOTE>
	 */
	public function restrictions(callable $do): QueryBuilder
	{
		return $this->startQueryBuilder()->restrictions($do);
	}
}
