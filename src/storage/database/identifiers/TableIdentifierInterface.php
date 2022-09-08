<?php namespace spitfire\storage\database\identifiers;

use spitfire\collection\Collection;

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
 * The query table wraps a table and provides a consistent aliasing mechanism.
 * This allows the system to reference tables within the database system across
 * queries.
 *
 * For example, when performing a query that requires a table to be joined twice,
 * the application needs to consistently alias the fields in the query. In SQL
 * we usually write something like
 *
 * SELECT * FROM orders LEFT JOIN customers c1 ON (...) LEFT JOIN customers c2 ON (...)
 *
 * And then reference the fields within them as c1.id or c2.id. Otherwise, the DBMS
 * will fail, indicating that the field `id` is ambiguous.
 */
interface TableIdentifierInterface extends IdentifierInterface
{
	
	/**
	 * Returns the components of the identifier. This can be any combination of
	 * strings that identifies a field or table inside the DBMS or a query.
	 *
	 * @return Collection<FieldIdentifierInterface>
	 */
	public function getOutputs(): Collection;
	
	/**
	 * Returns the components of the identifier. This can be any combination of
	 * strings that identifies a field or table inside the DBMS or a query.
	 *
	 * @return FieldIdentifier
	 */
	public function getOutput(string $name): FieldIdentifier;
	
	
	public function withAlias() : TableIdentifierInterface;
	
	
	public function getName() : string;
}
