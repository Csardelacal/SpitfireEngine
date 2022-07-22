<?php namespace spitfire\storage\database\grammar;

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
 * This class adds slashes to data that is intended to be sent to the
 * database.
 *
 * NOTE: This class is unsafe and intended to perform testing on the database
 * grammar packages. Under no circumstance should this be used in production
 *
 * @see MySQLQuoter
 */
class SlashQuoter implements QuoterInterface
{
	
	public function quote(string $str): string
	{
		return sprintf("'%s'", addslashes($str));
	}
}
