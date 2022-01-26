<?php namespace spitfire\storage\database\tests\grammar\mysql;

use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\Field;
use spitfire\storage\database\FieldReference;
use spitfire\storage\database\grammar\mysql\MySQLObjectGrammar;
use spitfire\storage\database\Layout;
use spitfire\storage\database\TableReference;

/**
 * The object grammar tests whether referencing tables and columns within the context
 * of queries is working properly.
 * 
 * @todo We need tests here for the MySQL Subquery grammar here.
 */
class MySQLObjectGrammarTest extends TestCase
{
	
	/**
	 * 
	 * @var MySQLObjectGrammar
	 */
	private $grammar;
	
	/**
	 * 
	 * @var TableReference
	 */
	private $queryTable;
	
	/**
	 * 
	 * @var FieldReference
	 */
	private $queryField;
	
	public function setUp() : void
	{
		$this->grammar = new MySQLObjectGrammar();
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field]));
		$layout->primary($field);
		
		$this->queryTable = $layout->getTableReference();
		$this->queryField = $this->queryTable->getOutput('testfield');
	}
	
	public function testQueryField()
	{
		$statement = $this->grammar->fieldReference($this->queryField);

		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield', $statement);
		$this->assertStringContainsString($this->queryField->getTable()->getName(), $statement);
		$this->assertStringContainsString($this->queryField->getName(), $statement);
	}
	
	public function testAggregate()
	{
		$aggregate = new Aggregate($this->queryField, 'COUNT', '__C__');
		$statement = $this->grammar->aggregate($aggregate);

		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield', $statement);
		$this->assertStringContainsString('COUNT', $statement);
		$this->assertStringContainsString('__C__', $statement);
		$this->assertStringContainsString($this->queryField->getName(), $statement);
	}
	
	public function testTable()
	{
		$statement = $this->grammar->tableReference($this->queryTable);
		$this->assertStringContainsString($this->queryTable->getName(), $statement);
	}
}
