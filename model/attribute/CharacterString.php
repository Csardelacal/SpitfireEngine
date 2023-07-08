<?php namespace spitfire\model\attribute;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-13 01  USA
 *
 */


use Attribute;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;

/**
 * The column attribute can be attached to a property of a model, allowing the
 * application to automatically generate fields or columns for the given element.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class CharacterString extends Type
{
	
	/**
	 *
	 * @var bool
	 */
	private ?bool $nullable;
	
	private int $length;
	
	public function __construct(int $length = 255, bool $nullable = null)
	{
		$this->nullable = $nullable;
		$this->length = $length;
		
		assert($length > 0);
	}
	
	/**
	 * Get the value of nullable
	 */
	public function isNullable() : ?bool
	{
		return $this->nullable;
	}
	
	public function getLength() : int
	{
		return $this->length;
	}
	
	public function migrate(SchemaMigrationExecutorInterface $schema, TableMigrationExecutorInterface $migrator, string $name, bool $nullable): void
	{
		$migrator->string($name, $this->length, $nullable);
	}
}
