<?php namespace spitfire\storage\database;

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
 * The index class represents an index on a DBMS, it contains the necessary
 * information for the application to know which data it has to index.
 * 
 * An Index allows your application to define a series of fields that the DBMS 
 * should index in order to improve performance of data retrieval.
 * 
 * Please note that indexes on the logical level are "suggestions" that allow
 * the DBMS to improve performance, but these are not required to be followed.
 */
class Index implements IndexInterface
{
	
	/**
	 * 
	 * @var string
	 */
	private $name;
	
	/**
	 * 
	 * @var Collection<Field>
	 */
	private $fields;
	
	/**
	 * 
	 * @var bool
	 */
	private $unique;
	
	/**
	 * 
	 * @var bool
	 */
	private $primary;
	
	/**
	 * Instance a new index. An index allows your application to instruct the 
	 * DBMS to maintain a sorted index that increases search performance of your
	 * application.
	 * 
	 * @param string $name
	 * @param Collection<Field> $fields
	 * @param bool $unique
	 * @param bool $primary
	 */
	public function __construct(string $name, Collection $fields, bool $unique = false, bool $primary = false)
	{
		$this->name = $name;
		$this->fields = $fields->each(fn($e) => clone $e);
		$this->unique = $unique;
		$this->primary = $primary;
	}
	
	/**
	 * Returns the name of the index. This is required for Spitfire to identify the 
	 * indexes it maintains and allows the application to manage the indexes.
	 * 
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}
	
	/**
	 * Returns the fields affected by this index. These should be fields that are used
	 * together when searching.
	 * 
	 * @return Collection<Field>
	 */
	public function getFields() : Collection
	{
		return $this->fields;
	}
	
	/**
	 * Indicates whether this is a unique index. Therefore requesting the DBMS to
	 * enforce no-duplicates on the index.
	 * 
	 * A driver requesting this value should always OR this value with isPrimary()
	 * like $index->isOptional() || $index->isPrimary() to know whether a index
	 * is unique.
	 * 
	 * @see IndexInterface::isPrimary()
	 * @return bool
	 */
	public function isUnique(): bool
	{
		return $this->unique || $this->primary;
	}
	
	/**
	 * Indicates whether this index is primary. If your index returns this value
	 * as true, the isUnique() value will be overriden by the system internally.
	 * 
	 * @return bool
	 */
	function isPrimary() : bool
	{
		return $this->primary;
	}
	
}
