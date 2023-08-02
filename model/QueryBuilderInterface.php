<?php namespace spitfire\model;

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


use spitfire\collection\Collection;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\Query as DatabaseQuery;

/**
 *
 * @template T of Model
 */
interface QueryBuilderInterface
{
	
	public function getQuery() : DatabaseQuery;
	
	/**
	 * @return ReflectionModel<T>
	 */
	public function getModel() : ReflectionModel;
	
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
	 * @return QueryBuilderInterface<T>
	 */
	public function restrictions(callable $do) : QueryBuilderInterface;
	
	/**
	 *
	 * @param string $type
	 * @param callable(RestrictionGroupBuilder):void $do
	 * @return QueryBuilderInterface<T>
	 */
	public function group(string $type, callable $do) : QueryBuilderInterface;
	
	/**
	 *
	 * @param callable():Model|null $or This function can either: return null, return a model
	 * or throw an exception
	 * @return Model|null
	 */
	public function first(callable $or = null):? Model;
	
	/**
	 *
	 * @return Collection<T>
	 */
	public function all() : Collection;
	
	/**
	 * @return Collection<T>
	 */
	public function range(int $offset, int $size) : Collection;
	
	public function count() : int;
	
	public function getConnection() : ConnectionInterface;
}
