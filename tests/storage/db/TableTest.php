<?php namespace tests\spitfire\storage\db;

use PHPUnit\Framework\TestCase;

class TableTest extends TestCase
{
	
	private $db;
	
	/**
	 * The table we're testing.
	 *
	 * @var \spitfire\storage\database\Table
	 */
	private $table;
	private $schema;
	
	public function setUp() {
		//Just in case Mr. Bergmann decides to add code to the setUp
		parent::setUp();
		
		//TODO: This needs to be replaced with logic that actually is properly testable.
		//Currently there is no DB mock driver. Not sure if I should create one or just test different drivers
		$this->db = db();
		
		$this->schema = new \spitfire\storage\database\Schema('test');
		
		$this->schema->field1 = new \IntegerField(true);
		$this->schema->field2 = new \StringField(255);
		
		$this->table = new \spitfire\storage\database\drivers\MysqlPDOTable($this->db, $this->schema);
	}
	
	public function testGetField() {
		$this->assertInstanceOf(\spitfire\storage\database\Field::class, $this->table->getLayout()->getField('field1'));
		$this->assertInstanceOf(\spitfire\storage\database\Field::class, $this->table->getLayout()->getField('field2'));
		
		//This checks that the table identifies and returns when an object is provided
		$this->assertInstanceOf(\spitfire\storage\database\Field::class, $this->table->getLayout()->getField($this->table->getLayout()->getField('field2')));
	}
	
	/**
	 * @expectedException \spitfire\exceptions\PrivateException
	 */
	public function tsetGetUnexistingFieldByName() {
		$this->table->getField('unexistingfield');
	}
	
	/**
	 * @expectedException \spitfire\exceptions\PrivateException
	 */
	public function testGetUnexistingFieldByObject() {
		$schema = new \spitfire\storage\database\Schema('test\storage\database\Table\notreal');
		$this->db->table($schema);
		$schema->field = new \IntegerField();
		$this->table->getLayout()->getField(new \spitfire\storage\database\drivers\mysqlPDOField($schema->field, 'notexisting'));
	}
	
	public function testCreate() {
		
		
		$schema1 = new \spitfire\storage\database\Schema('test\storage\database\Table\Create1');
		$schema2 = new \spitfire\storage\database\Schema('test\storage\database\Table\Create2');
		
		$schema2->a = new \Reference('test\storage\database\Table\Create1');
		
		$table1 = $this->db->table($schema1);
		$table2 = $this->db->table($schema2);
		
		$table2->getLayout()->destroy();
		$table1->getLayout()->destroy();
		
		$table1->getLayout()->create();
		$table2->getLayout()->create();
		
		$this->assertInstanceOf(\spitfire\storage\database\Table::class, $table2);
		
		$table2->getLayout()->destroy();
		$table1->getLayout()->destroy();
	}


	public function testFieldTypes() {
		$this->assertEquals(\spitfire\model\Field::TYPE_STRING, $this->table->getLayout()->getField('field2')->getLogicalField()->getDataType());
	}
	
}
