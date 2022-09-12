<?php namespace tests\spitfire\model\relations;

use PHPUnit\Framework\TestCase;
use spitfire\model\attribute\Table;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\query\ExtendedRestrictionGroupBuilder;
use spitfire\model\query\RestrictionGroupBuilder;
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
use spitfire\storage\database\Schema;
use tests\spitfire\model\fixtures\TestModel;

class BelongsToOneInjectorTest extends TestCase
{
	private static $connection;
	
	/**
	 * Resets the connection between tests.
	 */
	public function setUp() : void
	{
		self::$connection = null;
	}
	
	/**
	 * This test is a bit nonsensical, because the Model would usually be eagerly loaded
	 * with the appropriate data. But this code would be executed if belongstoone models
	 * were lazy loaded at some point.
	 */
	public function testCreateQuery()
	{
		
		$model = new #[Table('test2')] class(self::connection()) extends Model
		{
			
			private $_id;
			private $test;
			
			public function remote() : BelongsToOne
			{
				return new BelongsToOne(
					new Field($this, 'test'),
					new Field(new TestModel(BelongsToOneInjectorTest::connection()), 'example2')
				);
			}
			
			public function setTest(TestModel $t)
			{
				$this->test = $t;
			}
		};
		
		$query = $model->query();
		$query->restrictions(
			fn(ExtendedRestrictionGroupBuilder $builder) => $builder->has(
				'remote',
				fn(RestrictionGroupBuilder $query) => $query->where('example', 1)
			)
		);
		
		$query->first();
		$queries = self::$connection->getAdapter()->getDriver()->queries;
		$this->assertStringContainsString("WHERE EXISTS (SELECT", $queries[0]);
		
		$this->assertStringMatchesFormat(
			"SELECT `test2_%d`.`_id`, `test2_%d`.`test` " .
			"FROM `test2` AS `test2_%d` " .
			"WHERE EXISTS (SELECT `test_%d`.`example2` " .
			"FROM `test` AS `test_%d` WHERE " .
			"`test_%d`.`example` = '1' AND ".
			"`test_%d`.`example2` = `test2_%d`.`test`)",
			$queries[0]
		);
	}
	
	/**
	 */
	public function testCreateQueryWithNotExists()
	{
		
		$model = new #[Table('test2')] class(self::connection()) extends Model
		{
			
			private $_id;
			private $test;
			
			public function remote() : BelongsToOne
			{
				return new BelongsToOne(
					new Field($this, 'test'),
					new Field(new TestModel(BelongsToOneInjectorTest::connection()), 'example2')
				);
			}
			
			public function setTest(TestModel $t)
			{
				$this->test = $t;
			}
		};
		
		$query = $model->query();
		$query->restrictions(
			fn(ExtendedRestrictionGroupBuilder $builder) => $builder->hasNo(
				'remote',
				fn(RestrictionGroupBuilder $query) => $query->where('example', 1)
			)
		);
		
		$query->first();
		$queries = self::$connection->getAdapter()->getDriver()->queries;
		
		$this->assertStringMatchesFormat(
			"SELECT `test2_%d`.`_id`, `test2_%d`.`test` " .
			"FROM `test2` AS `test2_%d` " .
			"WHERE NOT EXISTS (SELECT `test_%d`.`example2` " .
			"FROM `test` AS `test_%d` WHERE " .
			"`test_%d`.`example` = '1' AND ".
			"`test_%d`.`example2` = `test2_%d`.`test`)",
			$queries[0]
		);
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
					return new AbstractResultSet([[
						'_id' => 1,
						'test' => 1
					]]);
				}
				
				public function lastInsertId(): string|false
				{
					return 1;
				}
			};
			
			$schema = new Schema('test');
			$migrator = new SchemaMigrationExecutor($schema);
			
			$migrator->add('test2', function (TableMigrationExecutor $t) {
				$t->id();
				$t->int('test', true);
			});
			
			$migrator->add('test', function (TableMigrationExecutor $t) {
				$t->int('test', true);
				$t->int('example', true);
				$t->int('example2', true);
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
