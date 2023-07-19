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

use spitfire\collection\OutOfBoundsException;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\Record;

/**
 *
 * @todo Extrapolate ModelFactoryInterface
 */
class ModelFactory
{
	
	private ConnectionInterface $connection;
	
	public function __construct(ConnectionInterface $connection)
	{
		$this->connection = $connection;
	}
	
	/**
	 *
	 * @template K of Model
	 * @param class-string<K> $className
	 * @return ReflectionModel<K>
	 * @todo Caching would probably help this gain some performance
	 */
	public function make(string $className) : ReflectionModel
	{
		return new ReflectionModel($className);
	}
	
	/**
	 *
	 * @template K of Model
	 * @param class-string<K> $className
	 * @return QueryBuilderBuilder<K>
	 */
	public function from(string $className) : QueryBuilderBuilder
	{
		/**
		 * @var QueryBuilderBuilder<K>
		 */
		return new QueryBuilderBuilder($this->connection, $this->make($className));
	}
	
	/**
	 *
	 * @template K of Model
	 * @param class-string<K> $className
	 * @param int|string $id
	 * @return K|null
	 */
	public function find(string $className, $id) :? Model
	{
		$model = $this->make($className);
		$query = (new QueryBuilder($this->connection, $model))->withDefaultMapping();
		return $query->find($id);
	}
	
	/**
	 *
	 * @template K of Model
	 * @throws OutOfBoundsException
	 * @param class-string<K> $className
	 */
	public function create(string $className) : Model
	{
		$model  = $this->make($className);
		$empty  = [];
		$layout = $this->connection->getSchema()->getLayoutByName($model->getTableName());
		
		foreach ($layout->getFields() as $field) {
			/**
			 * @todo Adding defaults here would be super fly. But this depends on the fields and migrators
			 * providing support for defaults so we can pull them here.
			 */
			$empty[$field->getName()] = null;
		}
		
		$record = new Record($empty);
		return $model->newInstance()->withHydrate(new ActiveRecord($this->connection, $model, $record));
	}
}
