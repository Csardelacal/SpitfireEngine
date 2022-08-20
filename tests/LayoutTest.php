<?php namespace spitfire\storage\database\tests;

use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\storage\database\Field;
use spitfire\storage\database\Index;
use spitfire\storage\database\Layout;

class LayoutTest extends TestCase
{
	
	public function testputIndex()
	{
		$layout = new Layout('testtable');
		$fields = new Collection([
			new Field('testfield1', 'int', false, true),
			new Field('testfield2', 'string:255', true)
		]);
		
		$index  = new Index('testidx', $fields, true, false);
		$layout->addFields($fields);
		
		$layout->putIndex($index);
		
		/**
		 * Check if the indexes for the table now contains an element,
		 * if the layout isn't empty, we have succeeded.
		 */
		$this->assertEquals(1, $layout->getIndexes()->count());
	}
}
