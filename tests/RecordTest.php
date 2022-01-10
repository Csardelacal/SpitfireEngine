<?php namespace tests\spitfire\storage\database;

use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\storage\database\Field;
use spitfire\storage\database\Index;
use spitfire\storage\database\Layout;
use spitfire\storage\database\QueryTable;
use spitfire\storage\database\Record;

class RecordTest extends TestCase
{
	private $layout;
	private $table;
	
	public function setUp() : void
	{
		$this->layout = new Layout('test');
		$this->table  = new QueryTable($this->layout);
		
		/**
		 * Add a primary key
		 */
		$field = $this->layout->putField('test', 'int', false, true);
		$index = new Index('testindex', new Collection([$field]), true, true);
		
		$this->layout->putIndex($index);
		
		/**
		 * Add a second field
		 */
		$this->layout->putField('test2', 'string:255', false, false);
	}
	
	public function testGetPrimary()
	{
		$record = new Record($this->layout, ['test' => 1, 'test2' => 'test']);
		$this->assertEquals(1, $record->getPrimary());
	}
	
	public function testIsChanged()
	{
		$record = new Record($this->layout, ['test' => 1, 'test2' => 'test']);
		$record->set('test2', 'test2');
		
		$this->assertEquals(true, $record->isChanged());
		$this->assertEquals(true, $record->isChanged('test2'));
		$this->assertEquals(false, $record->isChanged('test'));
	}
	
	public function testDiff()
	{
		$record = new Record($this->layout, ['test' => 1, 'test2' => 'test']);
		$record->set('test2', 'test2');
		
		$this->assertEquals(['test2' => 'test2'], $record->diff());
	}
	
	public function testCommit()
	{
		$record = new Record($this->layout, ['test' => 1, 'test2' => 'test']);
		$record->set('test2', 'test2');
		
		$this->assertEquals(true, $record->isChanged());
		
		$record->commit();
		$this->assertEquals(false, $record->isChanged());
	}
	
	public function testRollback()
	{
		$record = new Record($this->layout, ['test' => 1, 'test2' => 'test']);
		$record->set('test2', 'test2');
		
		$this->assertEquals(true, $record->isChanged());
		
		$record->rollback();
		$this->assertEquals(false, $record->isChanged());
		$this->assertEquals('test', $record->get('test2'));
	}
}
