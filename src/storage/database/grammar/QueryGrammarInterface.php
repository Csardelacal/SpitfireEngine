<?php namespace spitfire\storage\database\grammar;

use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\identifiers\IdentifierInterface;
use spitfire\storage\database\OrderBy;
use spitfire\storage\database\Query;
use spitfire\storage\database\query\Join;
use spitfire\storage\database\query\JoinQuery;
use spitfire\storage\database\query\JoinTable;
use spitfire\storage\database\QuoterInterface;
use spitfire\storage\database\query\Restriction;
use spitfire\storage\database\query\RestrictionGroup;
use spitfire\storage\database\query\SelectExpression;

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
 * This class aggregates the logic to create record related SQL statements,
 * allowing the application to abstract it's behavior a little further.
 */
interface QueryGrammarInterface
{
	
	/**
	 * Stringify a SQL query for MySQL.
	 *
	 * @param Query $query
	 * @return string
	 */
	public function query(Query $query) : string;
}
