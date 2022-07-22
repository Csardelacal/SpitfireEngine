<?php namespace spitfire\storage\database\query;

use spitfire\storage\database\Query;
use spitfire\storage\database\Subquery;

/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-1301  USA
 */
/**
 * Joins are used in databases to create cross products of data that can 
 * be filtered. Allowing the application to retrieve more specific data from
 * the server without the need to perform multiple trips to the DBMS.
 */
class JoinQuery extends Join
{
	
	/**
	 * 
	 * @var Subquery
	 */
	private $query;
	
	/**
	 * Instance a new join. This takes a Query (which may connect a physical table or a 
	 * temorary table / query).
	 * 
	 * @param Query $query
	 */
	public function __construct(Query $query)
	{
		$this->query = new Subquery($query);
		parent::__construct($query->getFrom()->output());
	}
	
	/**
	 * The query query that is used as a source. Please note that a query query can contain a query returning
	 * a temp query that is used to retrieve a subset of the original relation.
	 * 
	 * @return Subquery
	 */
	public function getSubQuery() : Subquery
	{
		return $this->query;
	}
}
