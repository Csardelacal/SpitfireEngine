<?php namespace tests\spitfire\model;

use PHPUnit\Framework\TestCase;
use spitfire\model\Model;
use spitfire\model\traits\WithId;
use spitfire\model\traits\WithTimestamps;
use spitfire\storage\database\Schema;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\test\AbstractDriver;
use spitfire\storage\database\drivers\test\AbstractResultSet;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\Layout;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;

class StoreTest extends TestCase
{
	
	public function testInsert()
	{
		
		$layout = new Layout('test');
		$migrator = new TableMigrationExecutor($layout);
		$migrator->id();
		$migrator->string('my_stick', 255);
		$migrator->string('my_test', 255);
		$migrator->timestamps();
		
		$schema = new Schema('sftest');
		$schema->putLayout($layout);
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([
					['_id' => 1, 'my_stick' => '', 'my_test' => '']
				]);
			}
			
			public function write(string $sql): int
			{
				$this->queries[] = $sql;
				return 1;
			}
			
			public function lastInsertId(): string|false
			{
				return '1';
			}
		};
		
		$connection = new Connection(
			$schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter()),
				new MySQLSchemaGrammar
			)
		);
		
		$model = new class ($connection) extends Model {
			use WithId, WithTimestamps;
			
			private string $my_stick;
			private string $my_test;
			
			public function getId()
			{
				return $this->_id;
			}
			
			public function getTableName()
			{
				return 'test';
			}
			
			public function setStick(string $string)
			{
				$this->my_stick = $string;
			}
		};
		
		$instance = $model->withHydrate(new Record([
			'_id' => null,
			'my_stick' => '',
			'my_test'  => '',
			'created'  => null,
			'updated'  => null
		]));
		
		$instance->store();
		$this->assertEquals(1, $instance->getId());
	}
}
