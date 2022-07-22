<?php namespace spitfire\storage\database\tests\grammar\mysql;

use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\Field;
use spitfire\storage\database\FieldReference;
use spitfire\storage\database\grammar\mysql\MySQLObjectGrammar;
use spitfire\storage\database\identifiers\IdentifierInterface;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
use spitfire\storage\database\Layout;
use spitfire\storage\database\query\SelectExpression;
use spitfire\storage\database\TableReference;

/**
 * The object grammar tests whether referencing tables and columns within the context
 * of queries is working properly.
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
	 * @var TableIdentifierInterface
	 */
	private $queryTable;
	
	/**
	 *
	 * @var IdentifierInterface
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
		$statement = $this->grammar->identifier($this->queryField);
		$raw = $this->queryField->raw();
		
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield', $statement);
		$this->assertStringContainsString($raw[0], $statement);
		$this->assertStringContainsString($raw[1], $statement);
	}
	
	public function testAggregate()
	{
		$aggregate = new Aggregate(Aggregate::AGGREGATE_COUNT);
		$statement = $this->grammar->selectExpression(new SelectExpression($this->queryField, '__C__', $aggregate));
		$raw = $this->queryField->raw();
		
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield', $statement);
		$this->assertStringContainsString('count', $statement);
		$this->assertStringContainsString('__C__', $statement);
		$this->assertStringContainsString($raw[1], $statement);
	}
	
	public function testTable()
	{
		$statement = $this->grammar->identifier($this->queryTable);
		$this->assertStringContainsString($this->queryTable->getName(), $statement);
	}
}
