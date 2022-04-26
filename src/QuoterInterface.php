<?php namespace spitfire\storage\database;

use PDO;

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
 * A quoter allows a database grammar to properly escape data that it is being
 * made part of a SQL Query, preventing malicious users from executing any
 * SQL injection attacks.
 *
 * The strongest use case for this is testing. Some DBMS require an existing
 * connection with the DBMS to quote input, which is safer, but also requires
 * the server to be running the affected DBMS.
 *
 * By using this class we can test the grammar without the need for a DBMS to
 * be available at all.
 */
interface QuoterInterface
{
	
	/**
	 * Add quotes to the output. This method is intended to return the exact same
	 * output as PDO::quote would.
	 *
	 * @see PDO::quote
	 */
	public function quote(string $str): string;
}
