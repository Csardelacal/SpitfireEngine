<?php namespace spitfire\storage\database;

use spitfire\collection\Collection;
use spitfire\storage\database\identifiers\FieldIdentifier;
use spitfire\storage\database\identifiers\FieldIdentifierInterface;
use spitfire\storage\database\identifiers\IdentifierInterface;
use spitfire\storage\database\identifiers\TableIdentifier;
use spitfire\storage\database\identifiers\TableIdentifierInterface;

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
 * A foreign key is an index that allows the application to 'link' two
 * columns from different tables so that the database can manage the
 * relationship between them for us.
 */
class ForeignKey implements ForeignKeyInterface
{
	
	/**
	 * The name of the index. This can be used to reference the index, while most
	 * databases support anonymous indexes, they usually auto-assign a name that
	 * they'll use. For spitfire, it makes it almost unmanageable to have anon
	 * indexes.
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The field that the 'current' table holds, this field will only be allowed
	 * to contain data that exists within the scope of the referenced field.
	 *
	 * @var Field
	 */
	private $field;
	
	/**
	 * The field that this index references, this field may contain data that this
	 * index does not allow, but the index will not allow it's own field to contain
	 * data that is not in the referenced field.
	 *
	 * @var IdentifierInterface
	 */
	private $referenced;
	
	/**
	 *
	 * @param string $name
	 * @param Field $field
	 * @param IdentifierInterface $referenced
	 */
	public function __construct(string $name, Field $field, IdentifierInterface $referenced)
	{
		$this->name  = $name;
		$this->field = clone $field;
		$this->referenced = $referenced;
	}
	
	/**
	 * Returns the name of the index. This allows the application to reference it when adding / removing
	 * / manipulating the index on the DBMS.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}
	
	/**
	 * Returns the fields managed by this index. The current version of Spitfire
	 * does not support foreign indexes with more than one field. But for the sake
	 * of compatibility with other indexes, the index returns a collection of fields.
	 *
	 * @return Collection<Field>
	 */
	public function getFields() : Collection
	{
		return new Collection([$this->field]);
	}
	
	/**
	 * Returns the fields referenced by this index. The current version of Spitfire
	 * does not support foreign indexes with more than one field. But for the sake
	 * of compatibility with other indexes, the index returns a collection of fields.
	 *
	 * In the event of the application planning to return multiple fields as part
	 * of the index, they would have to be appropriately sorted, so they correlate.
	 *
	 * @return Collection<FieldIdentifierInterface>
	 */
	public function getReferencedField(): Collection
	{
		return new Collection([$this->referenced]);
	}
	
	/**
	 * Return the table the foreign key is referencing to.
	 *
	 * @return TableIdentifierInterface
	 */
	public function getReferencedTable(): TableIdentifierInterface
	{
		$raw = $this->referenced->raw();
		array_pop($raw);
		
		$table = new TableIdentifier($raw, new Collection());
		
		return $table;
	}
	
	/**
	 * The current version of spitfire does not account for unique foreign key indexes.
	 * These would allow to define 1:1 relations by limiting the amount of duplicates
	 * that can be referencedField.
	 *
	 * @return bool
	 */
	public function isUnique(): bool
	{
		return false;
	}
	
	/**
	 * Just like unique indexes, primary indexes would be used to shape relations that
	 * are not intended to be used with spitfire.
	 *
	 * @return bool
	 */
	public function isPrimary(): bool
	{
		return false;
	}
}
