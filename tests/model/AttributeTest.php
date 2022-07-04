<?php namespace tests\spitfire\model;

use PHPUnit\Framework\TestCase;
use ReflectionAttribute;
use ReflectionClass;
use spitfire\model\attribute\Table;
use tests\spitfire\model\fixtures\TestModel;

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
}
