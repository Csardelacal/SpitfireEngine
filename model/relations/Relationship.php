<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\QueryBuilder;
use spitfire\storage\database\Query;
use spitfire\storage\database\query\RestrictionGroup;
use spitfire\utils\Mixin;

/**
 *
 * @template LOCAL of Model
 * @template REMOTE of Model
 * @implements RelationshipInterface<LOCAL,REMOTE>
 * @mixin QueryBuilder<REMOTE>
 */
abstract class Relationship implements RelationshipInterface
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
}
