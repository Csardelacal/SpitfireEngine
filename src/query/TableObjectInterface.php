<?php namespace spitfire\storage\database\query;

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
 * This interface represents objects in the database that can be used within
 * the context of queries
 */
interface TableObjectInterface
{
	
	/**
	 * Returns the outputs available to the interface. This allows the application to work
	 * with it's outputs appropriately.
	 * 
	 * @return Collection<OutputObjectInterface>
	 */
	function getOutputs() : Collection;
	
	/**
	 * Returns the name of the item when using it in context.
	 * 
	 * @return string
	 */
	function getAlias() : string;
}
