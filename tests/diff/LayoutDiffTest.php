<?php namespace spitfire\storage\database\tests\diff;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\diff\Generator;
use spitfire\storage\database\Layout;

class LayoutDiffTest extends TestCase
{
	
	public function testAddingRight()
	{
		$left = new Layout('test');
		$right = new Layout('test');
		
		$right->putField('test', 'int', true, false);
		
		$diff = (new Generator($left, $right))->make();
		
		$this->assertCount(1, $diff->left()->getFields());
	}
	
	public function testAddingLeft()
	{
		$left = new Layout('test');
		$right = new Layout('test');
		
		$left->putField('test', 'int', true, false);
		
		$diff = (new Generator($left, $right))->make();
		
		$this->assertCount(0, $diff->left()->getFields());
		$this->assertCount(1, $diff->right()->getFields());
	}
	
	public function testDivergedChanges()
	{
		$left = new Layout('test');
		$right = new Layout('test');
		
		$left->putField('test', 'int', true, false);
		$right->putField('test', 'string:255', true, false);
		
		$diff = (new Generator($left, $right))->make();
		
		$this->assertCount(1, $diff->left()->getFields());
		$this->assertCount(1, $diff->right()->getFields());
	}
	
	public function testIndexAddition()
	{
		$left = new Layout('test');
		$left->putField('test', 'int', true, false);
		$left->putField('test2', 'int', true, false);
		
		$right = clone $left;
		$right->index('test_idx', $right->getField('test'));
		
		$diff = (new Generator($left, $right))->make();
		
		$this->assertCount(1, $diff->left()->getIndexes());
		$this->assertCount(0, $diff->right()->getIndexes());
	}
	
	public function testIndexRemoval()
	{
		$left = new Layout('test');
		$left->putField('test', 'int', true, false);
		$left->putField('test2', 'int', true, false);
		
		$right = clone $left;
		$left->index('test_idx', $left->getField('test'));
		
		$diff = (new Generator($left, $right))->make();
		
		$this->assertCount(0, $diff->left()->getIndexes());
		$this->assertCount(1, $diff->right()->getIndexes());
	}
	
	public function testIndexChange()
	{
		$left = new Layout('test');
		$left->putField('test', 'int', true, false);
		$left->putField('test2', 'int', true, false);
		
		$right = clone $left;
		$left->index('test_idx', $left->getField('test'));
		$right->unique('test_idx', $right->getField('test'));
		
		$diff = (new Generator($left, $right))->make();
		
		$this->assertCount(1, $diff->left()->getIndexes());
		$this->assertCount(1, $diff->right()->getIndexes());
	}
	
	public function testIndexChangeFields()
	{
		$left = new Layout('test');
		$left->putField('test', 'int', true, false);
		$left->putField('test2', 'int', true, false);
		
		$right = clone $left;
		$left->index('test_idx', $left->getField('test'), $left->getField('test2'));
		$right->index('test_idx', $right->getField('test2'), $right->getField('test'));
		
		$diff = (new Generator($left, $right))->make();
		
		$this->assertCount(1, $diff->left()->getIndexes());
		$this->assertCount(1, $diff->right()->getIndexes());
	}
	
	public function testIndexNoChange()
	{
		$left = new Layout('test');
		$left->putField('test', 'int', true, false);
		$left->putField('test2', 'int', true, false);
		
		$right = clone $left;
		$left->index('test_idx', $left->getField('test'), $left->getField('test2'));
		$right->index('test_idx', $right->getField('test'), $right->getField('test2'));
		
		$diff = (new Generator($left, $right))->make();
		
		$this->assertCount(0, $diff->left()->getIndexes());
		$this->assertCount(0, $diff->right()->getIndexes());
	}
}
