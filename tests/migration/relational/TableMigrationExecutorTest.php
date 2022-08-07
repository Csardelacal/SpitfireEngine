<?php namespace spitfire\storage\database\tests\migration\relational;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\test\AbstractDriver;
use spitfire\storage\database\drivers\test\AbstractResultSet;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\migration\relational\TableMigrationExecutor;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor as SchemaStateTableMigrationExecutor;
use spitfire\storage\database\query\ResultInterface;
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
class TableMigrationExecutorTest extends TestCase
{
	
	
	public function testForeignKey()
	{
		
		$driver = new class extends AbstractDriver
		{
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([
					[]
				]);
			}
			
			public function write(string $sql): int
			{
				$this->queries[] = $sql;
				return 1;
			}
			
			public function lastInsertId(): string|false
			{
				return '1';
			}
		};
		
		$adapter = new Adapter(
			$driver,
			new MySQLQueryGrammar(new SlashQuoter),
			new MySQLRecordGrammar(new SlashQuoter),
			new MySQLSchemaGrammar()
		);
		
		$schema = new Schema('test');
		$layout = $schema->newLayout('test');
		$layout2 = $schema->newLayout('test2');
		
		$migrator = new SchemaStateTableMigrationExecutor($layout);
		$migrator->id();
		
		$migrator2 = new TableMigrationExecutor($adapter, $layout2);
		$migrator2->foreign('test', $migrator);
		
		$this->assertCount(1, $driver->queries);
		$this->assertStringNotContainsString('NOT NULL', $driver->queries[0]);
	}
}
