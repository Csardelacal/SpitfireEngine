<?php namespace tests\spitfire\model;

use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;
use spitfire\model\attribute\Table;
use spitfire\model\utils\AttributeLayoutGenerator;
use spitfire\storage\database\Field;
use spitfire\storage\database\ForeignKeyInterface;
use spitfire\storage\database\Index;
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
		$generator = new AttributeLayoutGenerator();
		
		$layout = $generator->make($reflection);
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
		
		$layout = $generator->make($reflection);
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
		
		$layout = $generator->make($reflection);
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
		
		$layout = $generator->make($reflection);
		$indexes = $layout->getIndexes()->filter(fn($e) => $e instanceof ForeignKeyInterface);
		$this->assertCount(1, $indexes);
		
		$index = $indexes->first();
		
		$this->assertCount(1, $index->getFields());
		$this->assertEquals('fk_foreign', $index->getName());
		$this->assertEquals('foreign', $index->getFields()[0]->getName());
	}
	
	public function testWithImpliedColumns()
	{
		$reflection = new ReflectionClass(TestModelWithImpliedColumns::class);
		$generator = new AttributeLayoutGenerator();
		
		$layout = $generator->make($reflection);
		
		#$this->assertEquals(true, $layout->hasField('_id'));
		$this->assertEquals(true, $layout->hasField('created'));
		$this->assertEquals(true, $layout->hasField('updated'));
		$this->assertEquals(true, $layout->hasField('removed'));
	}
}
