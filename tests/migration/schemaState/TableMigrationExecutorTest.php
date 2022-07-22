<?php namespace spitfire\storage\database\tests\migration\schemaState;

use spitfire\storage\database\Schema;
use PHPUnit\Framework\TestCase;
use spitfire\storage\database\Connection;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\internal\MockDriver;
use spitfire\storage\database\drivers\test\AbstractDriver;
use spitfire\storage\database\drivers\test\AbstractResultSet;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor;
use spitfire\storage\database\events\RecordBeforeDeleteEvent;
use spitfire\storage\database\events\RecordBeforeInsertEvent;
use spitfire\storage\database\Field;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\grammar\QueryGrammarInterface;
use spitfire\storage\database\grammar\RecordGrammarInterface;
use spitfire\storage\database\grammar\SchemaGrammarInterface;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;

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
	
	public function testIncrements()
	{
		$schema = new Schema('test');
		$layout = $schema->newLayout('test');
		
		$migrator = new TableMigrationExecutor($layout);
		$migrator->increments('test');
		
		$this->assertInstanceOf(Field::class, $layout->getField('test'));
		$this->assertEquals(1, $layout->getFields()->count());
		$this->assertEquals(1, $layout->getIndexes()->count());
	}
	
	public function testInt()
	{
		$schema = new Schema('test');
		$layout = $schema->newLayout('test');
		
		$migrator = new TableMigrationExecutor($layout);
		$migrator->int('test', false);
		
		$this->assertInstanceOf(Field::class, $layout->getField('test'));
		$this->assertEquals(1, $layout->getFields()->count());
		$this->assertEquals(0, $layout->getIndexes()->count());
	}
	
	public function testIndex()
	{
		$schema = new Schema('test');
		$layout = $schema->newLayout('test');
		
		$migrator = new TableMigrationExecutor($layout);
		$migrator->int('test', false);
		$migrator->int('test2', false);
		
		$migrator->index('test_idx', ['test', 'test2']);
		
		$this->assertInstanceOf(Field::class, $layout->getField('test'));
		$this->assertInstanceOf(Field::class, $layout->getField('test2'));
		
		$this->assertEquals(2, $layout->getFields()->count());
		$this->assertEquals(1, $layout->getIndexes()->count());
		$this->assertEquals(2, $layout->getIndexes()[0]->getFields()->count());
	}
	
	public function testTimestamps()
	{
		$schema = new Schema('test');
		$layout = $schema->newLayout('test');
		
		$migrator = new TableMigrationExecutor($layout);
		$migrator->id();
		$migrator->timestamps();
		
		$record = new Record(['created' => null]);
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([]);
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
		
		$connection = new Connection(
			$schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter),
				new MySQLSchemaGrammar
			)
		);
		
		$layout->events()->dispatch(new RecordBeforeInsertEvent($connection, $layout, $record), function () {
		});
		
		$this->assertNotEquals($record->get('created'), null);
		$this->assertEquals($record->get('created'), time());
	}
	
	public function testSoftDelete()
	{
		$schema = new Schema('test');
		$layout = $schema->newLayout('test');
		
		$migrator = new TableMigrationExecutor($layout);
		$migrator->id();
		$migrator->softDelete();
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([]);
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
		
		$connection = new Connection(
			$schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter),
				new MySQLSchemaGrammar
			)
		);
		
		$record = new Record(['removed' => null]);
		$event  = new RecordBeforeDeleteEvent($connection, $layout, $record);
		$called = false;
		
		$layout->events()->dispatch(
			$event,
			function () use (&$called) {
				$called = true;
			}
		);
		
		$time = time();
		
		$this->assertNotEquals($record->get('removed'), null);
		$this->assertEquals($record->get('removed'), $time);
		$this->assertEquals($called, false);
		
		$this->assertEquals(1, count($driver->queries));
		$this->assertStringContainsStringIgnoringCase('update', $driver->queries[0]);
		$this->assertStringContainsString($time, $driver->queries[0]);
	}
}
