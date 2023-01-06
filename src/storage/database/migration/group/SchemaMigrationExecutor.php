<?php namespace spitfire\storage\database\migration\group;

use BadMethodCallException;
use Closure;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;
use spitfire\storage\database\Layout;
use spitfire\storage\database\migration\TagManager;
use spitfire\storage\database\migration\TagManagerInterface;
use spitfire\storage\database\Schema;

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
 * The internal schema migrator makes it easy for an application to maintain
 * a schema that will allow the Models on top of it to perform validation and
 * similar tasks.
 */
class SchemaMigrationExecutor implements SchemaMigrationExecutorInterface
{
	
	/**
	 * The schema will contain all the migrated tables and data. Please note that
	 * since this is a reference, the data is being written to the reference directly.
	 *
	 * @var Collection<SchemaMigrationExecutorInterface>
	 */
	private $migrators;
	
	/**
	 *
	 * @param Collection<SchemaMigrationExecutorInterface> $migrators
	 */
	public function __construct(Collection $migrators)
	{
		assert($migrators->containsOnly(SchemaMigrationExecutorInterface::class));
		$this->migrators = $migrators;
	}
	
	/**
	 * Add the table to the schema. Before writing it to the schema, the application may
	 * alter the table.
	 *
	 * @param string $name
	 * @param Closure $fn
	 * @return SchemaMigrationExecutorInterface
	 */
	public function add(string $name, Closure $fn): SchemaMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->add($name, $fn);
		}
		
		return $this;
	}
	
	/**
	 * Rename the table on the schema.
	 *
	 * @param string $from
	 * @param string $to
	 * @return SchemaMigrationExecutorInterface
	 */
	public function rename(string $from, string $to): SchemaMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->rename($from, $to);
		}
		
		return $this;
	}
	
	/**
	 * Removes the layout from the schema.
	 *
	 * @param string $name
	 * @return SchemaMigrationExecutorInterface
	 */
	public function drop(string $name): SchemaMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->drop($name);
		}
		return $this;
	}
	
	/**
	 * Allows the application to descend into migrations pertaining to the table.
	 *
	 * @param string $name
	 * @return TableMigrationExecutorInterface
	 */
	public function table(string $name): TableMigrationExecutorInterface
	{
		$result = $this->migrators->each(
			function (SchemaMigrationExecutorInterface $e) use ($name) : TableMigrationExecutorInterface {
				return $e->table($name);
			}
		);
		
		return new TableMigrationExecutor($result);
	}
	
	public function has(string $name): bool
	{
		$result = $this->migrators->each(function (SchemaMigrationExecutorInterface $e) use ($name) : bool {
			return $e->has($name);
		});
		
		/**
		 * It should be a given that the results are consistent between the different underlying migrators
		 */
		assert($result->unique()->count() === 1);
		
		return !!$result->first();
	}
	
	/**
	 * The internal schema migrator cannot execute any SQL code. Invoking this method
	 * will lead to nothing happening.
	 *
	 * @param string $sql
	 * @return SchemaMigrationExecutorInterface
	 */
	public function execute(string $sql): SchemaMigrationExecutorInterface
	{
		foreach ($this->migrators as $migrator) {
			$migrator->execute($sql);
		}
		
		return $this;
	}
	
	/**
	 * Since this migrator is a group migrator, the application should just pass the call to all
	 * of it's underlying children.
	 * 
	 * @param callable(SchemaMigrationExecutorInterface):void $do
	 * @return void
	 */
	public function each(callable $do) : void
	{
		$this->migrators->each($do);
	}
	
	public function tags(): TagManagerInterface
	{
		$first = $this->migrators->first();
		assert($first !== null);
		
		return $first->tags();
	}
}
