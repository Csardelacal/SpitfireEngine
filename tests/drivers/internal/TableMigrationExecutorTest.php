<?php namespace spitfire\storage\database\tests\drivers\internal;

use Exception;
use spitfire\storage\database\Schema;
use PHPUnit\Framework\TestCase;
use spitfire\event\Event;
use spitfire\storage\database\drivers\internal\MockDriver;
use spitfire\storage\database\drivers\internal\SchemaMigrationExecutor;
use spitfire\storage\database\drivers\internal\TableMigrationExecutor;
use spitfire\storage\database\events\RecordBeforeDeleteEvent;
use spitfire\storage\database\events\RecordBeforeInsertEvent;
use spitfire\storage\database\events\RecordEventPayload;
use spitfire\storage\database\Field;
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
		
		$layout->events()->dispatch(new RecordBeforeInsertEvent(new MockDriver, $layout, $record), function () {
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
		
		$driver = new MockDriver;
		$record = new Record(['removed' => null]);
		$event  = new RecordBeforeDeleteEvent($driver, $layout, $record);
		$called = false;
		
		$layout->events()->dispatch(
			$event,
			function () use (&$called) {
				$called = true;
			}
		);
		
		$this->assertNotEquals($record->get('removed'), null);
		$this->assertEquals($record->get('removed'), time());
		$this->assertEquals($called, false);
		
		$this->assertEquals(1, count($driver->getLog()));
		$this->assertEquals('update', $driver->getLog()[0][0]);
		$this->assertNotEquals(null, $driver->getLog()[0][1]['removed']);
	}
}
