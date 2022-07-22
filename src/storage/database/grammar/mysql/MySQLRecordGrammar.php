<?php namespace spitfire\storage\database\grammar\mysql;

use PDO;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\grammar\RecordGrammarInterface;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\QuoterInterface;
use spitfire\storage\database\Record;

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
class MySQLRecordGrammar implements RecordGrammarInterface
{
	
	/**
	 *
	 * @var QuoterInterface
	 */
	private $quoter;
	
	public function __construct(QuoterInterface $quoter)
	{
		$this->quoter = $quoter;
	}
	
	public function updateRecord(LayoutInterface $layout, Record $record) : string
	{
		assert($layout->getPrimaryKey() !== null);
		
		$fields  = $layout->getFields();
		$primary = $layout->getPrimaryKey()->getFields()->first();
		$diff    = $record->diff();
		
		assert($primary !== null);
		
		$payload = $fields->each(function ($e) use ($diff) {
			if (!array_key_exists($e->getName(), $diff)) {
				return null;
			}
			return sprintf('`%s` = %s', $e->getName(), $this->quote($diff[$e->getName()]));
		})->filter();
		
		$stmt = (new Collection([
			'UPDATE',
			$layout->getTableName(),
			'SET',
			$payload->join(', '),
			'WHERE',
			sprintf('`%s`', $layout->getPrimaryKey()->getFields()[0]->getName()),
			'=',
			$this->quote($record->original($primary->getName()))
		]))->join(' ');
		
		return $stmt;
	}
	
	public function insertRecord(LayoutInterface $layout, Record $record) : string
	{
		$fields  = $layout->getFields();
		$raw     = $record->raw();
		
		$payload = $fields->each(function ($e) use ($raw) {
			return $this->quote(array_key_exists($e->getName(), $raw)? $raw[$e->getName()] : null);
		})->filter();
		
		$_fields = $fields->each(function ($e) {
			return sprintf('`%s`', $e->getName());
		})->join(', ');
		
		$stmt = (new Collection([
			'INSERT INTO',
			$layout->getTableName(),
			'(', $_fields, ')',
			'VALUES',
			'(', $payload->join(', '), ')'
		]))->join(' ');
		
		return $stmt;
	}
	
	/**
	 * Generates a valid SQL statement to delete the provided record from the database.
	 * Note that since SF 2020 we do no longer support compound primary keys, which means
	 * that primary keys with more than one field will cause issues.
	 *
	 * @param Record $record
	 * @return string
	 */
	public function deleteRecord(LayoutInterface $layout, Record $record) : string
	{
		assert($layout->getPrimaryKey() !== null);
		
		$primary = $layout->getPrimaryKey()->getFields()->first();
		
		assert($primary !== null);
		
		$stmt = (new Collection([
			'DELETE FROM',
			$layout->getTableName(),
			'WHERE',
			sprintf('`%s`', $layout->getPrimaryKey()->getFields()[0]->getName()),
			'=',
			$record->get($primary->getName())
		]))->join(' ');
		
		return $stmt;
	}
	
	/**
	 * Escapes a string to be used in a SQL statement. PDO offers this
	 * functionality out of the box so there's nothing to do.
	 *
	 * @param string|int|bool|null $text
	 * @return string Quoted and escaped string
	 */
	public function quote($text)
	{
		if ($text === null) {
			return 'null';
		}
		if (is_int($text)) {
			return sprintf('\'%s\'', strval($text));
		}
		if ($text === false) {
			return "'0'";
		}
		if ($text === true) {
			return "'1'";
		}
		
		return $this->quoter->quote($text);
	}
}
