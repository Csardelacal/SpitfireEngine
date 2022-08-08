<?php namespace tests\spitfire\model\relations;

use PHPUnit\Framework\TestCase;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\relations\BelongsToOne;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\test\AbstractDriver;
use spitfire\storage\database\drivers\test\AbstractResultSet;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\migration\schemaState\SchemaMigrationExecutor;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;
use spitfire\storage\database\Schema;
use tests\spitfire\model\fixtures\TestModel;

class BelongsToOneTest extends TestCase
{
	private static $connection;
	
		
	/**
	 * This test is a bit nonsensical, because the Model would usually be eagerly loaded
	 * with the appropriate data. But this code would be executed if belongstoone models
	 * were lazy loaded at some point.
	 */
	public function testCreateQuery()
	{
		
		$model = new class(self::connection()) extends Model
		{
			
			private $_id;
			private $test;
			
			public function remote() : BelongsToOne
			{
				return new BelongsToOne(
					new Field($this, 'test'),
					new Field(new TestModel(BelongsToOneTest::connection()), 'test')
				);
			}
			
			public function setTest(TestModel $t)
			{
				$this->test = $t;
			}
			
			public function getTableName()
			{
				return 'test';
			}
		};
		
		$instance = $model->withHydrate(new Record(['_id' => 1, 'test' => 1]));
		$query = $instance->remote()->getQuery();
		
		$query->first();
		$queries = self::$connection->getAdapter()->getDriver()->queries;
		$this->assertStringContainsString(".`test` = '1'", $queries[0]);
	}
	
	public static function connection()
	{
		if (!self::$connection) {
			$driver = new class extends AbstractDriver
			{
				public $queries = [];
				
				public function write(string $sql): int
				{
					return 0;
				}
				
				public function read(string $sql): ResultInterface
				{
					$this->queries[] = $sql;
					return new AbstractResultSet([['test' => 1]]);
				}
				
				public function lastInsertId(): string|false
				{
					return 1;
				}
			};
			
			$schema = new Schema('test');
			$migrator = new SchemaMigrationExecutor($schema);
			
			$migrator->add('test', function (TableMigrationExecutor $t) {
				$t->id();
				$t->int('test', true);
			});
			
			$migrator->add('TestModels', function (TableMigrationExecutor $t) {
				$t->int('test', true);
			});
			
			$adapter = new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter)
			);
			
			self::$connection = new Connection($schema, $adapter);
		}
		
		return self::$connection;
	}
}
