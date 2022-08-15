<?php namespace spitfire\model\relations;

use spitfire\model\query\RestrictionGroupBuilder;

/**
 * A relationship describes how two models connect with each other. This is useful
 * for navigating models and building queries. The injector provides a mechanism for
 * performing the necessary operations on the query to test whether related records
 * exist on it.
 */
interface RelationshipInjectorInterface
{
	
	/**
	 * 
	 * @param RestrictionGroupBuilder $query
	 * @param callable(RestrictionGroupBuilder):void|null $payload
	 */
	public function existence(RestrictionGroupBuilder $query, callable $payload = null) : void;
	
	/**
	 * Usually, testing for absence is symmetrical to testing for existence, but in order to allow
	 * the application to customize it if needed, this is an option.
	 * 
	 * @param RestrictionGroupBuilder $query
	 * @param null|callable(RestrictionGroupBuilder):void $payload
	 */
	public function absence(RestrictionGroupBuilder $query, callable $payload = null) : void;
}
