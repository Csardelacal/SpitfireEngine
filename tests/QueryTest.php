<?php namespace tests\spitfire\storage\database;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\Layout;
use spitfire\storage\database\AggregateFunction;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\Query;
use spitfire\storage\database\QueryTable;
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
		$query  = new Query($layout);
		
		$this->assertInstanceOf(QueryTable::class, $query->getQueryTable());
		$this->assertInstanceOf(LayoutInterface::class, $query->getTable());
	}
	
	public function testAssemblyDisambiguation()
	{
		$layout = new Layout('test');
		$querya = new Query($layout);
		$queryb = new Query($layout);
		
		$this->assertInstanceOf(QueryTable::class, $querya->getQueryTable());
		$this->assertInstanceOf(QueryTable::class, $queryb->getQueryTable());
		
		/**
		 * Our queries reference the same layout, but both use different querytables,
		 * this allows us to uniquely reference each query when working with them.
		 */
		$this->assertEquals($querya->getTable(), $queryb->getTable());
		
		$this->assertNotEquals($querya->getQueryTable(), $queryb->getQueryTable());
		$this->assertNotEquals($querya->getQueryTable()->getId(), $queryb->getQueryTable()->getId());
	}
	
}
