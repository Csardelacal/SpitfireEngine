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
class EnumType extends Type
{
	
	/**
	 *
	 * @var bool
	 */
	private ?bool $nullable;
	
	/**
	 *
	 * @var string[]
	 */
	private array $options;
	
	/**
	 *
	 * @param string[] $options
	 * @param bool $nullable
	 */
	public function __construct(array $options, bool $nullable = null)
	{
		$this->nullable = $nullable;
		$this->options = $options;
		
		assert(array_reduce($options, function (bool $carry, string $next) {
			return $carry && !empty($next);
		}, true));
	}
	
	/**
	 * Get the value of nullable
	 */
	public function isNullable() :? bool
	{
		return $this->nullable;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function getOptions() : array
	{
		return $this->options;
	}
	
	public function migrate(SchemaMigrationExecutorInterface $schema, TableMigrationExecutorInterface $migrator, string $name, bool $nullable): void
	{
		$migrator->enum($name, $this->options, $nullable);
	}
}
