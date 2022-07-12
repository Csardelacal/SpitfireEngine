<?php namespace spitfire\storage\database\tests\migration\schemaState;

use spitfire\storage\database\Schema;
use PHPUnit\Framework\TestCase;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\test\AbstractDriver;
use spitfire\storage\database\drivers\test\AbstractResultSet;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor;
use spitfire\storage\database\events\RecordBeforeDeleteEvent;
use spitfire\storage\database\events\RecordBeforeInsertEvent;
use spitfire\storage\database\Field;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
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
class SchemaMigrationForeignKeyTest extends TestCase
{
	
	/**
	 * This test makes sure that the system can create a foreign key with migrators.
	 */
	public function testForeignKey()
	{
		
		$schema = new Schema('test');
		$layout = $schema->newLayout('table1');
		$layout2 = $schema->newLayout('table2');
		
		$migrator2 = new TableMigrationExecutor($layout2);
		$migrator2->id();
		
		$migrator = new TableMigrationExecutor($layout);
		$migrator->id();
		$migrator->foreign('test', $migrator2);
		
		$this->assertCount(2, $layout->getIndexes());
	}
}
