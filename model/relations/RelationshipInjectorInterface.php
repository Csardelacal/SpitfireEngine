<?php namespace spitfire\model\relations;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */

use spitfire\model\query\ExtendedRestrictionGroupBuilder as RestrictionGroupBuilder;
use spitfire\model\Model;
use spitfire\model\QueryBuilder;
use spitfire\model\QueryBuilderBuilder;

/**
 * A relationship describes how two models connect with each other. This is useful
 * for navigating models and building queries. The injector provides a mechanism for
 * performing the necessary operations on the query to test whether related records
 * exist on it.
 *
 * @template REMOTE of Model
 */
interface RelationshipInjectorInterface
{
	
	/**
	 *
	 * @param RestrictionGroupBuilder<REMOTE> $query
	 * @param callable(QueryBuilderBuilder<REMOTE>):QueryBuilder<REMOTE> $payload
	 */
	public function existence(RestrictionGroupBuilder $query, callable $payload) : void;
	
	/**
	 * Usually, testing for absence is symmetrical to testing for existence, but in order to allow
	 * the application to customize it if needed, this is an option.
	 *
	 * @param RestrictionGroupBuilder<REMOTE> $query
	 * @param callable(QueryBuilderBuilder<REMOTE>):QueryBuilder<REMOTE> $payload
	 */
	public function absence(RestrictionGroupBuilder $query, callable $payload) : void;
}
