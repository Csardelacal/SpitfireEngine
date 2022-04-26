<?php namespace spitfire\storage\database\grammar\mysql;

use PDO;
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
 * This class aggregates the logic to create record related SQL statements,
 * allowing the application to abstract it's behavior a little further.
 */
class MySQLQuoter implements QuoterInterface
{
	
	/**
	 *
	 * @var PDO
	 */
	private $pdo;
	
	public function __construct(PDO $pdo)
	{
		$this->pdo = $pdo;
	}
	
	public function quote(string $str): string
	{
		return $this->pdo->quote($str);
	}
}
