<?php namespace tests\spitfire\model;
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

use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;
use spitfire\model\attribute\Table;
use spitfire\model\utils\AttributeLayoutGenerator;
use spitfire\storage\database\Field;
use spitfire\storage\database\ForeignKeyInterface;
use spitfire\storage\database\Index;
use spitfire\storage\database\migration\schemaState\SchemaMigrationExecutor;
use spitfire\storage\database\Schema;
use tests\spitfire\model\fixtures\ForeignModel;
use tests\spitfire\model\fixtures\TestModel;
use tests\spitfire\model\fixtures\TestModelWithImpliedColumns;

class AttributeTest extends TestCase
{
	
	public function testTableAttribute()
	{
		$reflection = new ReflectionClass(TestModel::class);
		$attributes = $reflection->getAttributes(Table::class);
		
		$this->assertCount(1, $attributes);
		
		$first = $attributes[0];
		$this->assertInstanceOf(ReflectionAttribute::class, $first);
		assert($first instanceof ReflectionAttribute);
		
		$attribute = $first->newInstance();
		$this->assertInstanceOf(Table::class, $attribute);
		$this->assertEquals('test', $attribute->getName());
	}
	
	public function testMakeLayoutFromModel()
	{
		$reflection = new ReflectionClass(TestModel::class);
		$schema = new Schema('test');
		$context = new SchemaMigrationExecutor($schema);
		$generator = new AttributeLayoutGenerator();
		
		$schema->putLayout($generator->make($context, new ReflectionClass(ForeignModel::class)));
		
		$layout = $generator->make($context, $reflection);
		$this->assertEquals('test', $layout->getTableName());
	}
	
	/**
	 * Make sure that the columns are added properly.
	 *
	 * @depends testMakeLayoutFromModel
	 */
	public function testMakeLayoutColumnsFromModel()
	{
		$reflection = new ReflectionClass(TestModel::class);
		$generator = new AttributeLayoutGenerator();
		
		$schema = new Schema('test');
		$context = new SchemaMigrationExecutor($schema);
		
		$schema->putLayout($generator->make($context, new ReflectionClass(ForeignModel::class)));
		
		$layout = $generator->make($context, $reflection);
		$this->assertCount(5, $layout->getFields());
		
		/**
		 * Make sure all the fields we added are available.
		 */
		$this->assertNotEmpty($layout->getFields()->filter(fn(Field $e) => $e->getName() === 'test')->first());
		$this->assertNotEmpty($layout->getFields()->filter(fn(Field $e) => $e->getName() === 'example')->first());
		$this->assertNotEmpty($layout->getFields()->filter(fn(Field $e) => $e->getName() === 'example2')->first());
		$this->assertNotEmpty($layout->getFields()->filter(fn(Field $e) => $e->getName() === 'foreign')->first());
		
		/**
		 * Check the type of the fields is correct
		 */
		$this->assertEquals('int:unsigned', $layout->getField('example')->getType());
		$this->assertEquals(true, $layout->getField('test')->isNullable());
	}
	
	public function testMakeLayoutIndexesFromModel()
	{
		$reflection = new ReflectionClass(TestModel::class);
		$generator = new AttributeLayoutGenerator();
		
		$schema = new Schema('test');
		$context = new SchemaMigrationExecutor($schema);
		
		$schema->putLayout($generator->make($context, new ReflectionClass(ForeignModel::class)));
		
		$layout = $generator->make($context, $reflection);
		$indexes = $layout->getIndexes()->filter(fn($e) => $e instanceof Index);
		$this->assertCount(2, $indexes);
		
		$index = $indexes[1];
		
		$this->assertCount(2, $index->getFields());
		$this->assertEquals('example2', $index->getFields()[0]->getName());
	}
	
	public function testMakeLayoutForeignKeysFromModel()
	{
		$reflection = new ReflectionClass(TestModel::class);
		$generator = new AttributeLayoutGenerator();
		
		$schema = new Schema('test');
		$context = new SchemaMigrationExecutor($schema);
		
		$schema->putLayout($generator->make($context, new ReflectionClass(ForeignModel::class)));
		
		$layout = $generator->make($context, $reflection);
		$indexes = $layout->getIndexes()->filter(fn($e) => $e instanceof ForeignKeyInterface);
		$this->assertCount(1, $indexes);
		
		$index = $indexes->first();
		
		$this->assertCount(1, $index->getFields());
		$this->assertEquals('fk_test_foreign', $index->getName());
		$this->assertEquals('foreign', $index->getFields()[0]->getName());
	}
	
	public function testWithImpliedColumns()
	{
		$reflection = new ReflectionClass(TestModelWithImpliedColumns::class);
		$generator = new AttributeLayoutGenerator();
		
		$schema = new Schema('test');
		$context = new SchemaMigrationExecutor($schema);
		
		$schema->putLayout($generator->make($context, new ReflectionClass(ForeignModel::class)));
		
		$layout = $generator->make($context, $reflection);
		
		#$this->assertEquals(true, $layout->hasField('_id'));
		$this->assertEquals(true, $layout->hasField('created'));
		$this->assertEquals(true, $layout->hasField('updated'));
		$this->assertEquals(true, $layout->hasField('removed'));
	}
}
