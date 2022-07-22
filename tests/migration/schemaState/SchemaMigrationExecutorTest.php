<?php namespace spitfire\storage\database\tests\migration\schemaState;

use spitfire\storage\database\Schema;
use PHPUnit\Framework\TestCase;
use spitfire\storage\database\migration\schemaState\SchemaMigrationExecutor;

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
class SchemaMigrationExecutorTest extends TestCase
{
	
	public function testRename()
	{
		$schema = new Schema('test');
		$layout = $schema->newLayout('test');
		
		$migrator = new SchemaMigrationExecutor($schema);
		$migrator->rename('test', 'hello');
		
		$this->assertEquals(true, $schema->hasLayoutByName('hello'));
		$this->assertEquals('hello', $schema->getLayoutByName('hello')->getTableName());
		$this->assertEquals(false, $schema->hasLayoutByName('test'));
	}
}
