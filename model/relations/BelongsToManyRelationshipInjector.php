<?php namespace spitfire\model\relations;

use BadMethodCallException;
use spitfire\collection\Collection;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\RestrictionGroup;

class BelongsToManyRelationshipInjector implements RelationshipInjectorInterface
{
	
	/**
	 * @var BelongsToMany
	 */
	private $relationship;
	
	
	public function __construct(BelongsToMany $relationship)
	{
		$this->relationship = $relationship;
	}
	
	
	/**
	 * If the query wishes to find items that belong to another model, all we
	 * have to do is look for those where the referencing field matches the
	 * id of the parent model.
	 *
	 * @todo Implement
	 * @param RestrictionGroup $query
	 * @param Model $model
	 * @return void
	 */
	public function injectWhere(RestrictionGroup $query, Model $model) : void
	{
		throw new BadMethodCallException('BelongsToMany Queries cannot be queried like this');
	}
	
	/**
	 * Allows the user to query for elements that match a certain condition in their remote
	 * counterpart. This could be used to, for example, to select the posts by users that
	 * have verified their email something like this:
	 *
	 * PostModel::query()->whereHas('user', function (Query $query) { $query->where('verified', true); });
	 *
	 * @todo Implement
	 * @param RestrictionGroup $query
	 * @param callable(RestrictionGroup):void $fn
	 * @return void
	 */
	public function injectWhereHas(RestrictionGroup $query, callable $fn) : void
	{
		throw new BadMethodCallException('Querying through belongstomany relationships is not yet implemented');
	}
}
