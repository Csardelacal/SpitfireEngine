<?php namespace spitfire\storage\database\tests\grammar\mysql;

use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\storage\database\Field;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Record;

class MySQLRecordGrammarTest extends TestCase
{
	
	/**
	 * 
	 * @var MySQLRecordGrammar
	 */
	private $grammar;
	
	public function setUp() : void
	{
		$this->grammar = new MySQLRecordGrammar(new SlashQuoter());
	}
	
	public function testUpdate()
	{
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field]));
		$layout->primary($field);
		
		$record = new Record($layout, ['testfield' => 1]);
		$record->set('testfield', 3);
		
		$this->assertArrayHasKey('testfield', $record->diff());
		
		$statement = $this->grammar->updateRecord($record);
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield', $statement);
		$this->assertStringContainsString('`testfield` = 1', $statement);
	}
	
	public function testUpdateOtherField()
	{
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', true, false);
		$field2  = new Field('testfield2', 'int', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field, 'testfield2' => $field2]));
		$layout->primary($field);
		
		$record = new Record($layout, ['testfield' => 1, 'testfield2' => 2]);
		$record->set('testfield', 3);
		$record->set('testfield2', 3);
		
		$this->assertArrayHasKey('testfield', $record->diff());
		
		$statement = $this->grammar->updateRecord($record);
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield', $statement);
		$this->assertStringContainsString('`testfield2` = 3', $statement);
		$this->assertStringContainsString('`testfield` = 1', $statement);
	}
	
	public function testInsertGrammar()
	{
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field]));
		$layout->primary($field);
		
		$record = new Record($layout, []);
		$record->set('testfield', 3);
		
		$this->assertArrayHasKey('testfield', $record->raw());
		
		$statement = $this->grammar->insertRecord($record);
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield', $statement);
		$this->assertStringContainsString('VALUES ( 3 )', $statement);
	}
	
	public function testInsertGrammarMultipleFields()
	{
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', true, false);
		$field2  = new Field('testfield2', 'int', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field, 'testfield2' => $field2]));
		$layout->primary($field);
		
		$record = new Record($layout, []);
		$record->set('testfield', 3);
		$record->set('testfield2', 4);
		
		$this->assertArrayHasKey('testfield', $record->raw());
		
		$statement = $this->grammar->insertRecord($record);
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield`, `testfield2', $statement);
		$this->assertStringContainsString('VALUES ( 3, 4 )', $statement);
	}
	
	public function testInsertGrammarMultipleFieldsWithNull()
	{
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', true, false);
		$field2  = new Field('testfield2', 'int', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field, 'testfield2' => $field2]));
		$layout->primary($field);
		
		$record = new Record($layout, []);
		$record->set('testfield', 3);
		
		$this->assertArrayHasKey('testfield', $record->raw());
		
		$statement = $this->grammar->insertRecord($record);
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield`, `testfield2', $statement);
		$this->assertStringContainsString('VALUES ( 3, null )', $statement);
	}
	
	public function testInsertGrammarMultipleFieldsWithString()
	{
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', true, false);
		$field2  = new Field('testfield2', 'string:255', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field, 'testfield2' => $field2]));
		$layout->primary($field);
		
		$record = new Record($layout, []);
		$record->set('testfield', 3);
		$record->set('testfield2', 'hello');
		
		$this->assertArrayHasKey('testfield', $record->raw());
		
		$statement = $this->grammar->insertRecord($record);
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('testfield`, `testfield2', $statement);
		$this->assertStringContainsString("VALUES ( 3, 'hello' )", $statement);
	}
	
	public function testDeleteGrammar()
	{
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', true, false);
		$field2  = new Field('testfield2', 'string:255', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field, 'testfield2' => $field2]));
		$layout->primary($field);
		
		$record = new Record($layout, ['testfield' => 3, 'testfield2' => 'hello']);
		
		$this->assertArrayHasKey('testfield', $record->raw());
		
		$statement = $this->grammar->deleteRecord($record);
		$this->assertStringContainsString('testtable', $statement);
		$this->assertStringContainsString('`testfield` = 3', $statement);
	}
}
