<?php namespace spitfire\storage\database\identifiers;

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
 *
 */
interface FieldIdentifierInterface extends IdentifierInterface
{
	
	/**
	 * Removes the scope attached (table and database) so that the field can be
	 * identified within the scope. This is important for stuff like operations
	 * where the sql requires us to generate an unqualified list of arguments like
	 * foreign keys:
	 *
	 * REFERENCES `tablename` (`field1`)
	 *
	 * @return FieldIdentifierInterface
	 */
	public function removeScope(): FieldIdentifierInterface;
	
	/**
	 * Returns the name of the field, as string. This is similar to removeScope, except
	 * this function will just return the string representation and not wrap it in a new
	 * field identifier.
	 *
	 * @return string
	 */
	public function getFieldName() : string;
}
