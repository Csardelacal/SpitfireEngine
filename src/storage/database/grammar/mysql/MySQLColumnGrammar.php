<?php namespace spitfire\storage\database\grammar\mysql;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\Field;
use spitfire\storage\database\ForeignKeyInterface;
use spitfire\storage\database\grammar\QueryGrammarInterface;
use spitfire\storage\database\identifiers\FieldIdentifierInterface;
use spitfire\storage\database\identifiers\IdentifierInterface;
use spitfire\storage\database\Index;
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
class MySQLColumnGrammar
{
	
	/**
	 *
	 * @var MySQLObjectGrammar
	 */
	private $object;
	
	public function __construct(QueryGrammarInterface $query)
	{
		$this->object = new MySQLObjectGrammar($query);
	}
	
	/**
	 * The column definition tells the DBMS the name, type and properties of a
	 * column in a table.
	 *
	 * @param Field $column
	 * @return string
	 */
	public function columnDefinition(Field $column) : string
	{
		return sprintf(
			'`%s` %s %s',
			$column->getName(),
			$this->translateDatatype($column->getType()),
			implode(' ', $this->generateAttributes($column))
		);
	}
	
	/**
	 * Since Spitfire has it's own way of defining the data types for the different
	 * DBMS it may support, the driver needs to appropriately translate these types
	 * to values the DBMS may understand.
	 *
	 * @see https://dev.mysql.com/doc/refman/5.7/en/data-types.html
	 * @throws ApplicationException
	 * @param string $type
	 * @return string
	 */
	protected function translateDatatype(string $type) : string
	{
		$pieces = explode(':', strtolower($type));
		$type = array_shift($pieces);
		
		if (!method_exists(MySQLTypeGrammar::class, $type)) {
			throw new ApplicationException('Invalid type: ' . $type);
		}
		
		return MySQLTypeGrammar::$type(...$pieces);
	}
	
	/**
	 * Generates a list of attributes for the column. Since Spitfire currently only
	 * shares support for nullable and auto_increment with MySQL it will just check
	 * whether the column has these features.
	 *
	 * @param Field $column
	 * @return string[]
	 */
	protected function generateAttributes(Field $column) : array
	{
		$_ret = [];
		
		/**
		 * Check whether the column is nullable or not. This is the base attribute we
		 * will add to all our columns.
		 */
		if ($column->isNullable()) {
			$_ret[] = 'NULL';
		}
		else {
			$_ret[] = 'NOT NULL';
		}
		
		/**
		 * If the column is an auto_increment column, we will add that information too.
		 */
		if ($column->isAutoIncrement()) {
			$_ret[] = 'AUTO_INCREMENT';
		}
		
		return $_ret;
	}
	/**
	 * Generates an index definition, which allows an application to add indexes to the
	 * table. This generally helps search performance significantly.
	 *
	 * @param IndexInterface $index
	 * @return string
	 */
	public function indexDefinition(IndexInterface $index) : string
	{
		/**
		 * One thing all indexes have in common is that they are applied
		 * to a list of fields.
		 */
		$fields = $index->getFields()->each(function (Field $field) {
			return sprintf('`%s`', $field->getName());
		});
		
		if ($index instanceof Index) {
			/**
			 * If the key is a primary key, we will create it with it's own syntax to prevent messing
			 * with the others.
			 */
			if ($index->isPrimary()) {
				return sprintf('PRIMARY KEY (%s)', $fields->join(', '));
			}
			
			/**
			 * All other indexes can be treated exactly the same.
			 */
			return sprintf(
				'%s %s (%s)',
				$index->isUnique()? 'UNIQUE INDEX' : 'INDEX',
				$index->getName(),
				$fields->join(', ')
			);
		}
		
		/**
		 * Foreign keys behave slightly differently to the normal indexes.
		 * They need additional information to report where the data on the other end
		 * is and how to handle it.
		 */
		elseif ($index instanceof ForeignKeyInterface) {
			$referenced  = $index->getReferencedField()->each(function (FieldIdentifierInterface $field) {
				return $this->object->identifier($field->removeScope());
			});
			
			return sprintf(
				'FOREIGN KEY %s (%s) REFERENCES %s (%s)',
				$index->getName(),
				$fields->join(', '),
				$this->object->identifier($index->getReferencedTable()),
				$referenced->join(', ')
			);
		}
		
		else {
			throw new ApplicationException('Invalid index', 2101182035);
		}
	}
}
