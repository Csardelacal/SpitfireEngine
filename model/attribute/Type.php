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
use spitfire\storage\database\drivers\TableMigrationExecutorInterface as MigratorInterface;

/**
 * Represents a database primitive type.
 */
abstract class Type
{
	
	abstract public function migrate(SchemaMigrationExecutorInterface $schema, MigratorInterface $migrator, string $name, bool $nullable): void;
}
