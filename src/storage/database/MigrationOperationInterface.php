<?php namespace spitfire\storage\database;

use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;

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
 * The migration operation interface allows applications to apply changes
 * to the schema in a controlled manner.
 */
interface MigrationOperationInterface
{
	
	/**
	 * The identifier is a human AND machine friendly way of identifying a migration,
	 * this MUST be unique and immutable. This identifier is used to keep track of
	 * which migrations are applied.
	 *
	 * @return string
	 */
	public function identifier() : string;
	
	/**
	 * Human readable description of the task the migration performs when calling
	 * up(), which implies that the migration will perform the opposite operation
	 * when invoking down()
	 *
	 * @return string
	 */
	public function description() : string;
	
	/**
	 * Upgrade the database to the state of the migration. The database will be left in
	 * the state of the current migration.
	 *
	 * @return void
	 */
	public function up(SchemaMigrationExecutorInterface $schema) : void;
	
	/**
	 * Downgrade the database to the state below the current migration. This means that
	 * Spitfire will find the previous highest migration and apply it's version number
	 * to the database.
	 */
	public function down(SchemaMigrationExecutorInterface $schema) : void;
}
