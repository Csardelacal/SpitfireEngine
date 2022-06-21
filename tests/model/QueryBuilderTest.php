<?php namespace tests\spitfire\model;

use PHPUnit\Framework\TestCase;
use spitfire\model\ConnectionManager;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\QueryBuilder;
use spitfire\model\relations\BelongsToOne;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\test\AbstractDriver;
use spitfire\storage\database\drivers\test\AbstractResultSet;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\Layout;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;
use spitfire\storage\database\Schema;

class QueryBuilderTest extends TestCase
{
	
	private $schema;
	
	private $layout;
	private $layout2;
	
	/**
	 * @var TestHandler
	 */
	private $logger;
	
	/**
	 *
	 * @var Model
	 */
	private $model;
	private $model2;
	
	public function setUp() : void
	{
		$this->layout = new Layout('test');
		$this->layout->putField('_id', 'int:unsigned', false, true);
		$this->layout->putField('my_stick', 'string:255', false, false);
		$this->layout->primary($this->layout->getField('_id'));
		
		$this->layout2 = new Layout('test2');
		$this->layout2->putField('_id', 'int:unsigned', false, true);
		$this->layout2->putField('test_id', 'int:unsigned', false, false);
		$this->layout2->putField('unrelated', 'string:255', false, false);
		$this->layout2->primary($this->layout->getField('_id'));
		$this->layout2->putIndex(new ForeignKey(
			'testforeign',
			$this->layout2->getField('test_id'),
			$this->layout->getTableReference()->getOutput('_id')
		));
		
		$this->schema = new Schema('sftest');
		$this->schema->putLayout($this->layout);
		$this->schema->putLayout($this->layout2);
		
		spitfire()->provider()->set(Schema::class, $this->schema);
		
		$this->model = new class ($this->layout) extends Model {
			
			public function getTableName()
			{
				return 'test';
			}
		};
		
		$this->model2 = new class ($this->model) extends Model {
			private $parent;
			
			public function __construct(Model $parent)
			{
				$this->parent = $parent;
			}
			
			public function test()
			{
				return new BelongsToOne(new Field($this, 'test_id'), new Field($this->parent, '_id'));
			}
			
			public function getTableName()
			{
				return 'test2';
			}
		};
		
		$schema = new Schema('test');
		$schema->putLayout($this->layout);
		$schema->putLayout($this->layout2);
		
		$id = rand();
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([]);
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
			$this->schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter()),
				new MySQLSchemaGrammar
			)
		);
		
		$manager = spitfire()->provider()->get(ConnectionManager::class);
		$manager->put($id, $connection);
		$manager->setDefault($id);
		
		spitfire()->provider()->set(ConnectionManager::class, $manager);
		
		$this->model->setConnection($id);
		$this->model2->setConnection($id);
	}
	
	public function testBelongsToWhere()
	{
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([]);
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
			$this->schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter()),
				new MySQLSchemaGrammar
			)
		);
		
		$builder = new QueryBuilder(
			$this->model2
		);
		
		$model = new class () extends Model {
			
			
			public function getTableName()
			{
				return 'test2';
			}
		};
		
		$model->setRecord(new Record(['_id' => 1]));
		
		$builder->where('test', $model);
		
		$connection->query($builder->getQuery());
		
		$this->assertStringContainsString('`test_id` = \'1\'', $driver->queries[0]);
	}
	
	public function testBelongsToWhereHas()
	{
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([]);
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
			$this->schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter()),
				new MySQLSchemaGrammar
			)
		);
		
		$builder = new QueryBuilder(
			$this->model2
		);
		
		$builder->whereHas('test', function (QueryBuilder $builder) {
			$builder->where('my_stick', 'is better than bacon');
		});
		
		$connection->query($builder->getQuery());
		
		$this->assertStringContainsString('WHERE EXISTS', $driver->queries[0]);
		$this->assertStringContainsString('`my_stick` = \'is better than bacon\'', $driver->queries[0]);
		$this->assertCount(1, $driver->queries);
		$this->assertStringContainsString('`_id` FROM `test`', $driver->queries[0]);
		$this->assertStringContainsString("`.`my_stick` = 'is better than bacon' AND", $driver->queries[0]);
	}
}
