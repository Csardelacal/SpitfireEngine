<?php namespace spitfire\storage\database\tests;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Restriction;
use spitfire\storage\database\QueryField;

class RestrictionTest extends TestCase
{
	
	/**
	 * @todo This is not workking until we fix the layout and TableReference accordingly and rebase them
	 */
	public function testNegate()
	{
		$layout = new Layout('table');
		$layout->putField('test', 'string:255', true, false);
		
		$table = $layout->getTableReference();
		$field = $table->getOutput('test');
		$restriction = new Restriction($field, 'test', Restriction::EQUAL_OPERATOR);
		
		$this->assertEquals('<>', $restriction->negate());
		$this->assertEquals('<>', $restriction->getOperator());
	}
}
