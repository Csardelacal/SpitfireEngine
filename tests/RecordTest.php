<?php namespace tests\spitfire\storage\database;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\Record;

class RecordTest extends TestCase
{
	
	public function testIsChanged()
	{
		$record = new Record(['test' => 1, 'test2' => 'test']);
		$record->set('test2', 'test2');
		
		$this->assertEquals(true, $record->isChanged());
		$this->assertEquals(true, $record->isChanged('test2'));
		$this->assertEquals(false, $record->isChanged('test'));
	}
	
	public function testSlice()
	{
		$record = new Record([
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4
		]);
		
		$slice = $record->slice(['a', 'c']);
		$this->assertEquals(false, $slice->has('b'));
	}
	
	public function testSliceEdited()
	{
		$record = new Record([
			'a' => 1,
			'b' => 2,
			'c' => 3,
			'd' => 4
		]);
		
		$record->set('b', 42);
		$slice = $record->slice(['b', 'c']);
		
		$this->assertEquals(false, $slice->has('a'));
		$this->assertEquals(42, $slice->get('b'));
		$this->assertEquals(2, $slice->original('b'));
		$this->assertEquals($record->diff(), $slice->diff());
	}
	
	public function testDiff()
	{
		$record = new Record(['test' => 1, 'test2' => 'test']);
		$record->set('test2', 'test2');
		
		$this->assertEquals(['test2' => 'test2'], $record->diff());
	}
	
	public function testCommit()
	{
		$record = new Record(['test' => 1, 'test2' => 'test']);
		$record->set('test2', 'test2');
		
		$this->assertEquals(true, $record->isChanged());
		
		$record->commit();
		$this->assertEquals(false, $record->isChanged());
	}
	
	public function testRollback()
	{
		$record = new Record(['test' => 1, 'test2' => 'test']);
		$record->set('test2', 'test2');
		
		$this->assertEquals(true, $record->isChanged());
		
		$record->rollback();
		$this->assertEquals(false, $record->isChanged());
		$this->assertEquals('test', $record->get('test2'));
	}
}
