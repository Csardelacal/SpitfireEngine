<?php namespace spitfire\storage\database\tests;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\Field;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Restriction;
use spitfire\storage\database\QueryField;
use spitfire\storage\database\QueryTable;

class RestrictionTest extends TestCase
{
	
	/**
	 * @todo This is not workking until we fix the layout and querytable accordingly and rebase them
	 */
	public function testNegate()
	{
		$layout = new Layout('table');
		$layout->setField('test', new Field($layout, 'test', 'string:255', true, false));
		
		$table = new QueryTable($layout);
		$field = new QueryField($table, $layout->getField('test'));
		$restriction = new Restriction($field, 'test', Restriction::EQUAL_OPERATOR);
		
		$this->assertEquals('<>', $restriction->negate());
		$this->assertEquals('<>', $restriction->getOperator());
	}
}
