<?php namespace spitfire\model\query;

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

use PDOException;
use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\Model;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;

/**
 * 
 * @todo If we're going to have pivots, it may be better to have them here than
 * in the ResultSetMapping (making the mapping recusrive)
 * 
 * @template T of Model
 */
class ResultSet
{
	
	/**
	 * 
	 * @var ResultSetMapping<T>
	 */
	private ResultSetMapping $map;
	
	/**
	 * The underlying resultset
	 *
	 * @var ResultInterface
	 */
	private ResultInterface $resultset;
	
	/**
	 * 
	 * @param ResultInterface $result
	 * @param ResultSetMapping<T> $map
	 */
	public function __construct(ResultInterface $result, ResultSetMapping $map)
	{
		$this->resultset = $result;
		$this->map = $map;
	}
	
	/**
	 * 
	 * @throws PDOException
	 * @return T|false
	 */
	public function fetch() : Model|false
	{
		$assoc = $this->resultset->fetchAssociative();
		
		if ($assoc === false) {
			return false;
		}
		
		return $this->map->makeOne(new Record($assoc));
	}
	
	/**
	 * 
	 * @throws PDOException
	 * @return Collection<T>
	 */
	public function fetchAll() : Collection
	{
		$all = Collection::fromArray($this->resultset->fetchAllAssociative());
		return $this->map->make($all);
	}
}
