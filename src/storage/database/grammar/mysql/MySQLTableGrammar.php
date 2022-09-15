<?php namespace spitfire\storage\database\grammar\mysql;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\Field;
use spitfire\storage\database\ForeignKeyInterface;
use spitfire\storage\database\grammar\QueryGrammarInterface;
use spitfire\storage\database\IndexInterface;
use spitfire\storage\database\QuoterInterface;

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
 * This grammar allows Spitfire to perform common operations on tables. This is usually
 * consumed by migration operations.
 */
class MySQLTableGrammar
{
	
	/**
	 * Grammar used for column operations.
	 *
	 * @var MySQLColumnGrammar
	 */
	private $columns;
	
	public function __construct(QueryGrammarInterface $query)
	{
		$this->columns = new MySQLColumnGrammar($query);
	}
	
	/**
	 * Generates the SQL necessary for performing a set of operations on a
	 * table.
	 *
	 * @param string $tablename
	 * @param string[] $operations
	 * @return string
	 */
	public function alterTable(string $tablename, array $operations) : string
	{
		return sprintf(
			'ALTER TABLE `%s` %s',
			$tablename,
			implode(', ', $operations)
		);
	}
	
	/**
	 * Generates the necessary SQL to add a column to the table. This uses the
	 * column definition grammar.
	 *
	 * @param Field $field
	 * @return string
	 */
	public function addColumn(Field $field) : string
	{
		return sprintf(
			'ADD COLUMN %s',
			$this->columns->columnDefinition($field)
		);
	}
	
	/**
	 *
	 * @param IndexInterface $index
	 * @return string
	 */
	public function addIndex(IndexInterface $index) : string
	{
		return sprintf(
			'ADD %s',
			$this->columns->indexDefinition($index)
		);
	}
	
	/**
	 * Generates the necessary syntax to remove a column from the DBMS.
	 *
	 * @param Field $field
	 * @return string
	 */
	public function dropColumn(Field $field) : string
	{
		return sprintf(
			'DROP COLUMN `%s`',
			$field->getName()
		);
	}
	
	/**
	 * Removes an index from the database.
	 *
	 * @param IndexInterface $index
	 * @return string
	 */
	public function dropIndex(IndexInterface $index) : string
	{
		/**
		 * If the key is a foreign key, we need to specify that in the MySQL Syntax.
		 */
		if ($index instanceof ForeignKeyInterface) {
			return sprintf('DROP FOREIGN KEY `%s`', $index->getName());
		}
		
		/**
		 * PRIMARY KEYS in MySQL behave in a special manner, since they are always named
		 * primary (making them effectively anonymous)
		 */
		if ($index->isPrimary()) {
			return 'DROP PRIMARY KEY';
		}
		
		/**
		 * All other indexes are dropped using the DROP INDEX clause. It doesn't matter
		 * whether the key is unique.
		 */
		return sprintf('DROP INDEX `%s`', $index->getName());
	}
}
