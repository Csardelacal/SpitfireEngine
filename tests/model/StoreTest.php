<?php namespace tests\spitfire\model;

use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\attribute\Table;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\relations\BelongsToOne;
use spitfire\model\relations\RelationshipContent;
use spitfire\model\traits\WithId;
use spitfire\model\traits\WithTimestamps;
use spitfire\storage\database\Schema;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;
use spitfire\storage\database\drivers\test\AbstractDriver;
use spitfire\storage\database\drivers\test\AbstractResultSet;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\Layout;
use spitfire\storage\database\migration\schemaState\SchemaMigrationExecutor;
use spitfire\storage\database\migration\schemaState\TableMigrationExecutor;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;
use tests\spitfire\model\fixtures\TestModel;

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
				new MySQLSchemaGrammar(new MySQLQueryGrammar(new SlashQuoter))
			)
		);
		
		$model = new #[Table('test')] class ($connection) extends Model {
			use WithId, WithTimestamps;
			
			private string $my_stick;
			private string $my_test;
			
			public function getId()
			{
				return $this->_id;
			}
			
			public function setStick(string $string)
			{
				$this->my_stick = $string;
			}
		};
		
		$instance = $model->withSelfHydrate(new Record([
			'_id' => null,
			'my_stick' => '',
			'my_test'  => '',
			'created'  => null,
			'updated'  => null
		]));
		
		$instance->store();
		$this->assertEquals(1, $instance->getId());
	}
	
	
	public function testInsertWithRelationships()
	{
		
		$schema = new Schema('sftest');
		$migrator = new SchemaMigrationExecutor($schema);
		$migrator->add('test_models', function (TableMigrationExecutor $t) {
			$t->id();
			$t->int('test', true);
			$t->int('example', true);
			$t->int('example2', true);
		});
		
		$migrator->add('test', function (TableMigrationExecutorInterface $table) use ($migrator) {
			$table->id();
			$table->foreign('foreign', $migrator->table('test_models'));
			$table->string('my_stick', 255);
			$table->string('my_test', 255);
			$table->timestamps();
		});
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([
					[
						'_id' => 1,
						'my_stick' => 'test',
						'my_test'  => 'test',
						'foreign'  => 1,
						'created'  => time(),
						'updated'  => time()
					]
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
				new MySQLSchemaGrammar(new MySQLQueryGrammar(new SlashQuoter))
			)
		);
		
		$model = new #[Table('test')] class ($connection, new TestModel($connection)) extends Model {
			use WithId, WithTimestamps;
			
			private string $my_stick;
			private string $my_test;
			private TestModel $foreign;
			
			private $parent;
			
			public function __construct($connection, $parent)
			{
				parent::__construct($connection);
				$this->parent = $parent;
			}
			
			public function getId()
			{
				return $this->_id;
			}
			
			public function setStick(string $string)
			{
				$this->my_stick = $string;
			}
			
			public function foreign() : BelongsToOne
			{
				return new BelongsToOne(
					new Field($this, 'foreign'),
					new Field($this->parent, '_id')
				);
			}
			
			public function setForeign($foreign)
			{
				$this->foreign = $foreign;
			}
			
			public function getForeign()
			{
				return $this->foreign;
			}
		};
		
		$activeRecord = new ActiveRecord($model, new Record([
			'_id' => null,
			'my_stick' => '',
			'my_test'  => '',
			'foreign'  => null,
			'created'  => null,
			'updated'  => null
		]));
		
		$foreign = (new TestModel($connection))->withSelfHydrate(new Record([
			'_id'  => 1,
			'test' => 'Hello world'
		]));
		
		$activeRecord->set('foreign', new RelationshipContent(true, new Collection([$foreign])));
		$instance = $model->withHydrate($activeRecord);
		
		$instance->store();
		$this->assertEquals('Hello world', $instance->getForeign()->getTest());
	}
}
