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

use InvalidArgumentException;
use PDOException;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\model\query\ExtendedRestrictionGroupBuilder;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\query\ResultSet;
use spitfire\model\query\ResultSetMapping;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\events\QueryBeforeCreateEvent;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\QueryOrTableIdentifier;
use spitfire\storage\database\query\RestrictionGroup;
use spitfire\utils\Mixin;

/**
 *
 * @template T of Model
 * @mixin RestrictionGroupBuilder
 */
class QueryBuilder implements QueryBuilderInterface
{
	
	use Mixin;
	
	private ConnectionInterface $connection;
	
	/**
	 *
	 * @var ReflectionModel<T>
	 */
	private ReflectionModel $model;
	
	/**
	 *
	 * @var ResultSetMapping<T>
	 */
	private ResultSetMapping $mapping;
	
	/**
	 * The with method allows the user to determine relations that should be
	 * proactively resolved.
	 *
	 * @var string[]
	 */
	private $with = [];
	
	/**
	 *
	 * @var DatabaseQuery
	 */
	private $query;
	
	/**
	 *
	 * @param ConnectionInterface $connection
	 * @param ReflectionModel<T> $model
	 * @param scalar[] $options
	 */
	public function __construct(ConnectionInterface $connection, ReflectionModel $model, array $options = [])
	{
		$this->model = $model;
		$this->connection = $connection;
		
		$table = $connection->getSchema()->getLayoutByName($model->getTableName());
		
		$this->query = new DatabaseQuery(new QueryOrTableIdentifier($table->getTableReference()));
		$this->mixin(fn() => new ExtendedRestrictionGroupBuilder($this, $this->query->getRestrictions()));
		
		$table->events()->dispatch(
			new QueryBeforeCreateEvent($this->connection, $this->query, $options)
		);
	}
	
	/**
	 *
	 * @return self<T>
	 */
	public function withDefaultMapping() : QueryBuilder
	{
		$copy = clone $this;
		
		/**
		 * We need to select all the fields from the table we're querying to push them into
		 * our model so it can be hydrated.
		 */
		$selected = $copy->query->selectAll();
		$map = new ResultSetMapping($this->connection, $this->model);
		
		foreach ($selected as $select) {
			$map->set($select->getName(), $select->getInput());
		}
		
		$copy->mapping = $map;
		
		return $copy;
	}
	
	/**
	 *
	 * @return ResultSetMapping<T>
	 */
	public function getMapping() : ResultSetMapping
	{
		return $this->mapping;
	}
	
	/**
	 *
	 * @param ResultSetMapping<T> $mapping
	 * @return self<T>
	 */
	public function withMapping(ResultSetMapping $mapping) : QueryBuilder
	{
		$copy = clone $this;
		$copy->mapping = $mapping;
		return $copy;
	}
	
	public function getQuery() : DatabaseQuery
	{
		return $this->query;
	}
	
	public function getModel() : ReflectionModel
	{
		return $this->model;
	}
	
	/**
	 *
	 * @throws InvalidArgumentException
	 * @param RestrictionGroup::TYPE_* $type
	 * @param callable(ExtendedRestrictionGroupBuilder):void $do
	 * @return self<T>
	 */
	public function group(string $type, callable $do) : QueryBuilder
	{
		$group = $this->query->getRestrictions()->group($type);
		$do(new ExtendedRestrictionGroupBuilder($this, $group));
		return $this;
	}
	
	public function getRestrictions(): RestrictionGroupBuilder
	{
		return new ExtendedRestrictionGroupBuilder($this, $this->query->getRestrictions());
	}
	
	/**
	 *
	 * @return self<T>
	 */
	public function restrictions(callable $do): QueryBuilder
	{
		$do($this->getRestrictions());
		return $this;
	}
	
	public function getConnection() : ConnectionInterface
	{
		return $this->connection;
	}
	
	/**
	 * Pass an array of strings with relationships that should be eagerly
	 * loaded when retrieving data.
	 *
	 * @param string[] $with
	 * @return self<T>
	 */
	public function with(array $with)
	{
		$this->with = $with;
		return $this;
	}
	
	/**
	 * 
	 * @return T
	 */
	public function find($id):? Model
	{
		$table = $this->connection->getSchema()->getLayoutByName($this->model->getTableName());
		$key = $table->getPrimaryKey()->getFields()->first();
		
		assert($key !== null);
		
		return $this->where($key->getName(), $id)->first();
	}
	
	/**
	 *
	 * @param mixed $args
	 * @return self<T>
	 */
	public function where(...$args) : QueryBuilder
	{
		(new ExtendedRestrictionGroupBuilder($this, $this->query->getRestrictions()))->where(...$args);
		return $this;
	}
	
	/**
	 *
	 * @param callable():Model|null $or This function can either: return null, return a model
	 * or throw an exception
	 * 
	 * @throws PDOException
	 * @return Model|null
	 */
	public function first(callable $or = null):? Model
	{
		/*
		* Fetch a single row from the database.
		*/
		$result = new ResultSet(
			$this->connection->query($this->getQuery()),
			$this->mapping->with($this->with)
		);
		
		$row = $result->fetch();
		
		/**
		 * If there is no more rows in the result (alas, there have never been any), the application
		 * should call the or() callable. This can either create a new record, return null or throw
		 * a user defined exception.
		 */
		if ($row === false) {
			return $or === null? null : $or();
		}
		
		return $row;
	}
	
	/**
	 *
	 * @throws PDOException
	 * @return Collection<Model>
	 */
	public function all() : Collection
	{
		/*
		* Fetch the records from the database
		*/
		$result = new ResultSet(
			$this->connection->query($this->getQuery()),
			$this->mapping->with($this->with)
		);
		
		return $result->fetchAll();
	}
	
	/**
	 *
	 * @throws PDOException
	 * @return Collection<T>
	 */
	public function range(int $offset, int $size) : Collection
	{
		/*
		 * Fetch a single row from the database.
		 */
		$query  = clone $this->getQuery();
		$query->range($offset, $size);
		
		/**
		 * Fetch the records from the database
		 * 
		 * @var ResultSet<T>
		 */
		$result = new ResultSet(
			$this->connection->query($query),
			$this->mapping->with($this->with)
		);
		
		return $result->fetchAll();
	}
	
	/**
	 *
	 * @throws PDOException
	 */
	public function count() : int
	{
		$query = $this->query->withoutSelect();
		
		/**
		 * Get the primary index, and make sure that it actually exists.
		 */
		$_table = $this->connection->getSchema()->getLayoutByName($this->model->getTableName());
		$_primary = $_table->getPrimaryKey();
		
		assert($_primary !== null);
		assert($_primary->getFields()->count() === 1);
		
		$primary = $_primary->getFields()->first();
		assert($primary !== null);
		
		$query->aggregate(
			$this->getQuery()->getFrom()->output()->getOutput($primary->getName()),
			new Aggregate(Aggregate::AGGREGATE_COUNT),
			'c'
		);
		
		
		$result = $this->connection->query($query)->fetchOne();
		assert($result !== false);
		assert(is_scalar(($result)));
		
		return (int)$result;
	}
	
	/**
	 * The advantage of counting records like this is that mysql will stop counting
	 * as soon as it found the n records it's supposed to look for.
	 *
	 * @throws PDOException
	 * @see https://sql-bits.com/check-if-more-than-n-rows-are-returned/
	 */
	public function quickCount(int $upto = 101) : int
	{
		$query = $this->query->withoutSelect();
		
		/**
		 * Get the primary index, and make sure that it actually exists. The primary key also must
		 * have exactly one field.
		 */
		$_table = $this->connection->getSchema()->getLayoutByName($this->model->getTableName());
		$_primary = $_table->getPrimaryKey();
		assert($_primary !== null);
		assert($_primary->getFields()->count() === 1);
		
		$primary = $_primary->getFields()->first();
		assert($primary !== null);
		
		/**
		 * Use the primary key for counting.
		 */
		$query->select($primary->getName());
		$query->range(0, $upto);
		
		/**
		 * Once the inner query is constructed, we wrap it into another query that actually performs
		 * the count. This means that the database server counts and returns only the calculated
		 * result, reducing the traffic between the machines.
		 */
		$outer = new DatabaseQuery(new QueryOrTableIdentifier($query));
		$outer->aggregate(
			$this->getQuery()->getFrom()->output()->getOutput($primary->getName())->removeScope(),
			new Aggregate(Aggregate::AGGREGATE_COUNT),
			'c'
		);
		
		$result = $this->connection->query($outer)->fetchOne();
		assert($result !== false);
		assert(is_scalar($result));
		
		return (int)$result;
	}
	
	/**
	 *
	 * @throws PDOException
	 * @throws ApplicationException If the field does not exist
	 */
	public function sum(string $fieldname) : int
	{
		$query = $this->query->withoutSelect();
		
		/**
		 * Get the primary index, and make sure that it actually exists.
		 */
		$query->aggregate(
			$this->getQuery()->getFrom()->output()->getOutput($fieldname),
			new Aggregate(Aggregate::AGGREGATE_SUM),
			'__SUM__'
		);
		
		
		$result = $this->connection->query($query)->fetchOne();
		assert($result !== false);
		assert(is_scalar(($result)));
		
		return (int)$result;
	}
}
