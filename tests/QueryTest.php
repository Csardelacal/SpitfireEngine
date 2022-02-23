<?php namespace tests\spitfire\storage\database;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Query;
use spitfire\storage\database\Table;

class QueryTest extends TestCase
{
	
	/**
	 * The table we're testing.
	 *
	 * @var Table
	 */
	private $table;
	private $schema;
	
	public function setUp() : void
	{
	}
	
	public function testAssembly()
	{
		$layout = new Layout('test');
		$query  = new Query($layout->getTableReference());
		
		$this->assertInstanceOf(TableIdentifierInterface::class, $query->getTable());
	}
	
	public function testAssemblyDisambiguation()
	{
		$layout = new Layout('test');
		$querya = new Query($layout->getTableReference());
		$queryb = new Query($layout->getTableReference());
		
		$this->assertInstanceOf(TableIdentifierInterface::class, $querya->getTable());
		$this->assertInstanceOf(TableIdentifierInterface::class, $queryb->getTable());
		
		/**
		 * Our queries reference the same layout, but both use different querytables,
		 * this allows us to uniquely reference each query when working with them.
		 */
		$this->assertEquals($querya->getFrom()->input()->raw(), $queryb->getFrom()->input()->raw());
		
		$this->assertNotEquals($querya->getFrom()->output(), $queryb->getFrom()->output());
		$this->assertNotEquals($querya->getFrom()->output()->raw(), $queryb->getFrom()->output()->raw());
	}
}
