<?php namespace spitfire\model\relations;

use spitfire\collection\Collection;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\RestrictionGroup;

class HasManyRelationshipInjector implements RelationshipInjectorInterface
{
	
	private $field;
	
	private $referenced;
	
	
	public function __construct(Field $field, Field $referenced)
	{
		$this->field = $field;
		$this->referenced = $referenced;
	}
	
	
	/**
	 * If the query wishes to find items that belong to another model, all we
	 * have to do is look for those where the referencing field matches the
	 * id of the parent model.
	 *
	 * @param RestrictionGroup $query
	 * @param Model $model
	 * @return void
	 */
	public function injectWhere(RestrictionGroup $query, Model $model) : void
	{
		/**
		 * If we're looking for the parent of an element, all we have to do is look for the
		 * element that the child is reporting is it's parent.
		 * 
		 * For example, if a user (user_id, created) and a shopping_cart(userid, items) interact
		 * we could assume that something like
		 * 
		 * User::find()->where('cart', $cart)->first()->created
		 * 
		 * Would just imply that we need to look which user_id the cart is registered to.
		 */
		$query->where(
			$this->field->getModel()->getTable()->getPrimaryKey()->getFields()->first()->getName(),
			$model->get($this->referenced->getField()->getName())
		);
	}
	
	/**
	 * Allows the user to query for elements that match a certain condition in their remote
	 * counterpart. This could be used to, for example, to select the posts by users that
	 * have verified their email something like this:
	 *
	 * PostModel::query()->whereHas('user', function (Query $query) { $query->where('verified', true); });
	 * 
	 * NOTE: Due to the way relationships work, this is extremely similar to the way belongsTo
	 * relationships are constructed. Even though they are similar, the differences are subtle
	 * and make a major difference in the output.
	 *
	 * @param RestrictionGroup $query
	 * @param callable(RestrictionGroup):void $fn
	 * @return void
	 */
	public function injectWhereHas(RestrictionGroup $query, callable $fn) : void
	{
		$query->whereExists(function (DatabaseQuery $parent) use ($fn) : DatabaseQuery {
			/**
			 * The subquery is just a regular query being performed on the remote model (the
			 * one being referenced).
			 */
			$subquery = $this->referenced->getModel()->query();
			
			/**
			 * We then let the developer apply any additional restrictions they whish to perform.
			 */
			$fn($subquery);
			
			/**
			 * After that, we need to access the underlying query that spitfire/database manages
			 * to inject the relation between the local and remote fields within the subquery.
			 */
			$query = $subquery->getQuery();
			$primary = $this->field->getField();
			
			$query->where(
				$query->getFrom()->output()->getOutput($this->referenced->getField()),
				$parent->getFrom()->output()->getOutput($primary)
			);
			
			/**
			 *
			 */
			$query->select($this->referenced->getField());
			
			return $query;
		});
	}
}
