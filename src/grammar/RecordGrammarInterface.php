<?php namespace spitfire\storage\database\grammar;

use PDO;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
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
interface RecordGrammarInterface
{
	
	public function updateRecord(LayoutInterface $layout, Record $record) : string;
	
	public function insertRecord(LayoutInterface $layout, Record $record) : string;
	
	/**
	 * Generates a valid SQL statement to delete the provided record from the database.
	 * Note that since SF 2020 we do no longer support compound primary keys, which means
	 * that primary keys with more than one field will cause issues.
	 *
	 * @param Record $record
	 * @return string
	 */
	public function deleteRecord(LayoutInterface $layout, Record $record) : string;
	
	/**
	 * Escapes a string to be used in a SQL statement. PDO offers this
	 * functionality out of the box so there's nothing to do.
	 *
	 * @param string|int|bool|null $text
	 * @return string Quoted and escaped string
	 */
	public function quote($text);
}
