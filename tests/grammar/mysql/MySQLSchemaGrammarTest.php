<?php namespace spitfire\storage\database\tests\grammar\mysql;

use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\storage\database\Field;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\Layout;

class MySQLSchemaGrammarTest extends TestCase
{
	
	/**
	 * Tests spitfire's ability to assemble a proper create table statement
	 * that can later be used to migrate data.
	 */
	public function testCreateTable()
	{
		$layout = new Layout('testtable');
		$field  = new Field('testfield', 'int', false, false);
		$field2 = new Field('testfiel2', 'int', true, false);
		
		$layout->addFields(new Collection(['testfield' => $field, 'testfiel2' => $field2]));
		$layout->primary($field);
		$layout->index('testidx', $field2);
		
		$grammar = new MySQLSchemaGrammar();
		$sql = $grammar->createTable($layout);
		
		$this->assertStringContainsString('CREATE TABLE', $sql);
		$this->assertStringContainsString(' `testtable` ', $sql);
		$this->assertStringContainsString('PRIMARY KEY (`testfield`)', $sql);
	}
	
	public function testRenameTable()
	{
		$grammar = new MySQLSchemaGrammar();
		$sql = $grammar->renameTable('hello-world', 'goodbye-world');
		
		$this->assertEquals('RENAME TABLE `hello-world` TO `goodbye-world`', $sql);
	}
	
	public function testDropTable()
	{
		$grammar = new MySQLSchemaGrammar();
		$sql = $grammar->dropTable('hello-world');
		
		$this->assertEquals('DROP TABLE `hello-world`', $sql);
	}
	
	public function testCreateTableWithForeignKey()
	{
		$foreign = new Layout('foreignlayout');
		$foreign->putField('id', 'int:unsigned', false, true);
		$foreign->primary($foreign->getField('id'));
		
		$layout = new Layout('testtable');
		$layout->putField('id', 'int:unsigned', false, true);
		$layout->primary($layout->getField('id'));
		$field  = new Field('testfield', 'int:unsigned', false, false);
		
		$layout->addFields(new Collection(['testfield' => $field]));
		$layout->primary($field);
		$layout->putIndex(new ForeignKey('foreignidx', $field, $foreign->getTableReference()->getOutput('id')));
		
		$grammar = new MySQLSchemaGrammar();
		$sql = $grammar->createTable($layout);
		
		$this->assertStringContainsString('CREATE TABLE', $sql);
		$this->assertStringContainsString(' `testtable` ', $sql);
		$this->assertStringContainsString(' `foreignlayout` ', $sql);
		$this->assertStringContainsString(' `foreignlayout` (`id`)', $sql);
	}
}
