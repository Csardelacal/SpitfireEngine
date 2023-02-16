<?php namespace spitfire\model\reflection;

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


use spitfire\model\attribute\Type;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;

class ReflectionField
{
	
	private string $name;
	private bool $nullable;
	private Type $type;
	
	public function __construct(string $name, bool $nullable, Type $type)
	{
		$this->name = $name;
		$this->type = $type;
		$this->nullable = $nullable;
	}
	
	/**
	 * Get the value of type
	 *
	 * @return Type
	 */
	public function getType(): Type
	{
		return $this->type;
	}
	
	/**
	 * Get the value of name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}
	
	/**
	 * Get the value of nullable
	 *
	 * @return bool
	 */
	public function getNullable(): bool
	{
		return $this->nullable;
	}
	
	public function migrate(TableMigrationExecutorInterface $migrator)
	{
		$this->type->migrate($migrator, $this->name, $this->nullable);
	}
}
