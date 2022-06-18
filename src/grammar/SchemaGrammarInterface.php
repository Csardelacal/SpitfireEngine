<?php namespace spitfire\storage\database\grammar;

use spitfire\storage\database\LayoutInterface;

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
 * A grammar class allows Spitfire to generate the SQL without executing it. This makes
 * it really simple to prepare scripts to be run in batch, outsourced to another app and
 * for unit testing.
 *
 * This grammar allows Spitfire to perform common operations on schemas. Most commonly,
 * migration related operations.
 */
interface SchemaGrammarInterface
{
	
	/**
	 * Prepares the necessary SQL to create a table on the DBMS.
	 *
	 * @see https://dev.mysql.com/doc/refman/8.0/en/create-table.html
	 * @param LayoutInterface $layout
	 * @return string
	 */
	public function createTable(LayoutInterface $layout) : string;
	
	/**
	 * The SQL to rename a table is almost trivial. This should make it really easy
	 * for our application to offer table renaming if needed.
	 *
	 * @param string $from
	 * @param string $to
	 * @return string
	 */
	public function renameTable(string $from, string $to) : string;
	
	/**
	 * Generates the necessary SQL to drop a table.
	 *
	 * @param string $tablename
	 * @return string
	 */
	public function dropTable(string $tablename);
	
	public function hasTable(string $schemaName, string $tableName) : string;
}
