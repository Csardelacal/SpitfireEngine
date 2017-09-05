<?php namespace tests\spitfire\storage\database\drivers\mysqlpdo;

use IntegerField;
use PHPUnit\Framework\TestCase;
use Reference;
use spitfire\exceptions\PrivateException;
use spitfire\storage\database\drivers\mysqlpdo\Driver;
use spitfire\storage\database\Schema;
use spitfire\storage\database\Table;
use StringField;

class TableTest extends TestCase
{
	
	private $db;
	
	/**
	 * The table we're testing.
	 *
	 * @var Table
	 */
	private $table;
	private $schema;
	
	public function setUp() {
		//Just in case Mr. Bergmann decides to add code to the setUp
		parent::setUp();
		
		try {
			$this->db = new Driver();
			$this->db->create();

			$this->schema = new Schema('test');

			$this->schema->field1 = new IntegerField(true);
			$this->schema->field2 = new StringField(255);

			$this->table = new Table($this->db, $this->schema);
		}
		catch (PrivateException$e) {
			$this->markTestSkipped('MySQL PDO driver is not available.');
		}
	}
	
	public function tearDown() {
		$this->db->destroy();
	}
	
	
	public function testCreate() {
		
		
		$schema1 = new Schema('test\storage\database\Table\Create1');
		$schema2 = new Schema('test\storage\database\Table\Create2');
		
		$schema2->a = new Reference('test\storage\database\Table\Create1');
		
		$table1 = $this->db->table($schema1);
		$table2 = $this->db->table($schema2);
		
		$table1->getLayout()->create();
		$table2->getLayout()->create();
		
		$this->assertInstanceOf(Table::class, $table2);
	}
	
}
