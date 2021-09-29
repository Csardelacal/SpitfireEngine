<?php namespace tests\spitfire\storage\database;

use PHPUnit\Framework\TestCase;
use spitfire\exceptions\ApplicationException;
use spitfire\exceptions\PrivateException;
use spitfire\model\Field as Field2;
use spitfire\model\fields\IntegerField;
use spitfire\model\fields\StringField;
use spitfire\storage\database\drivers\mysqlpdo\Field as MysqlField;
use spitfire\storage\database\Field;
use spitfire\model\Schema;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Settings;
use spitfire\storage\database\Table;

class TableTest extends TestCase
{
	
	private $db;
	
	/**
	 * The table we're testing.
	 *
	 * @var Table
	 */
	private $table;
	
	/**
	 * 
	 * @var Layout
	 */
	private $schema;
	
	public function setUp() : void 
	{
		//Just in case Mr. Bergmann decides to add code to the setUp
		parent::setUp();
		
		$this->schema = new Layout('test');
		$this->schema->putField('field1', 'int:unsigned', true);
		$this->schema->putField('field2', 'string:255', true);
	}
	
	public function testGetField()
	{
		$this->assertInstanceOf(Field::class, $this->schema->getField('field1'));
		$this->assertInstanceOf(Field::class, $this->schema->getField('field2'));
	}
	
	public function testGetUnexistingFieldByName() 
	{
		$this->expectException(ApplicationException::class);
		$this->schema->getField('unexistingfield');
	}
	
	public function testFieldTypes() 
	{
		$this->assertEquals('string:255', $this->schema->getField('field2')->getType());
	}
	
}
