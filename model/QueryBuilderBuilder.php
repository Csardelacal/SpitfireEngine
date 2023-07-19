<?php
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


namespace spitfire\model;

use Illuminate\Support\Traits\Macroable;
use spitfire\model\traits\WithSoftDeletes;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\events\SoftDeleteQueryListener;
use spitfire\utils\Lazy;

/**
 * 
 * @template T of Model
 * @mixin QueryBuilder<T>
 */
class QueryBuilderBuilder
{
	use Macroable {
		Macroable::__call as protected illuInvoke;
	}
	
	use Lazy {
		Lazy::__call as protected sfInvoke;
	}
	
	private ConnectionInterface $connection;
	private ReflectionModel $model;
	private array $options = [];
	
	public function __construct(ConnectionInterface $connection, ReflectionModel $model)
	{
		$this->connection = $connection;
		$this->model = $model;
		
		$this->delegate(
			fn() => (new QueryBuilder($this->connection, $this->model, $this->options))->withDefaultMapping()
		);
	}
	
	public function withOption(string $option, $value) : self
	{
		$copy = clone $this;
		$copy->options[$option] = $value;
		return $copy;
	}
	
	/**
	 * If the model is soft deleting, this method can be used to query only data
	 * that has been trashed.
	 */
	public function onlyTrashed() : self
	{
		assert($this->model->hasTrait(WithSoftDeletes::class));
		
		return $this->withOption(
			SoftDeleteQueryListener::OPTION__NAME,
			SoftDeleteQueryListener::OPTION_TRASHED
		);
	}
	
	public function withTrashed() : self
	{
		assert($this->model->hasTrait(WithSoftDeletes::class));
		
		return $this->withOption(
			SoftDeleteQueryListener::OPTION__NAME,
			SoftDeleteQueryListener::OPTION_INCLUDE
		);
	}
	
	public function __call($method, $parameters)
	{
		if (self::hasMacro($method)) {
			return $this->illuInvoke($method, $parameters);
		}
		
		return $this->sfInvoke($method, $parameters);
	}
}
