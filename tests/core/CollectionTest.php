<?php namespace tests\spitfire\core;

use BadMethodCallException;
use \PHPUnit\Framework\TestCase;
use spitfire\core\Collection;

class CollectionTest extends TestCase
{
	
	public function testAverage() {
		
		$collection = new Collection([1, 2, 3]);
		$this->assertEquals($collection->avg(), 2, 'Average of 1, 2 and 3 is 2');
		
	}
	
	/**
	 * 
	 * @expectedException BadMethodCallException
	 */
	public function testAverage2() {
		$collection = new Collection([]);
		$collection->avg();
	}
	
	/**
	 * 
	 * @expectedException BadMethodCallException
	 */
	public function testAverage3() {
		$collection = new Collection(['a', 'b', 'c']);
		$collection->avg();
	}
	
	public function testExtraction() {
		$collection = new Collection([['a' => 1, 'b' => 2], ['a' => 'c', 'b' => 4]]);
		$result     = $collection->each(function ($e) { return $e['b']; })->avg();
		
		$this->assertEquals($result, 3, 'The average value of 2 and 4 is expected to be 3');
	}
	
	public function testExtract() {
		$collection = new Collection([['a' => 1, 'b' => 2], ['a' => 'c', 'b' => 4]]);
		$result     = $collection->extract('b')->avg();
		
		$this->assertEquals($result, 3, 'The average value of 2 and 4 is expected to be 3');
	}
	
	/**
	 * Tests whether the filtering algorithm of a collection works properly.
	 */
	public function testFilter() {
		$collection = new Collection([0, 1, 0, 2, 0, 3]);
		
		$this->assertInstanceOf(Collection::class, $collection->filter());
		$this->assertEquals(3, $collection->filter()->count());
		$this->assertEquals(1, $collection->filter(function ($e) { return $e === 1; })->pluck());
	}
	
	
}