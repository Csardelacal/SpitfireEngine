<?php namespace tests\spitfire\model;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use spitfire\model\ConnectionManager;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\QueryBuilder;
use spitfire\model\relations\BelongsToOne;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\mysqlpdo\NoopDriver;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Record;
use spitfire\storage\database\Schema;
use spitfire\storage\database\Settings;

class QueryBuilderTest extends TestCase
{
	
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
		
		
		$this->model = new class ($this->layout) extends Model {
			public function getTableName()
			{
				return 'test';
			}
		};
		
		$this->model2 = new class ($this->model) extends Model {
			private $parent;
			
			public function __construct($parent)
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
		
		$connection = new Connection(
			$schema,
			new NoopDriver(
				Settings::fromArray(['schema' => 'sftest', 'port' => 3306, 'password' => 'root']),
				new Logger('test', [$this->logger = new TestHandler()])
			)
		);
		
		$manager = spitfire()->provider()->get(ConnectionManager::class);
		$manager->put($id, $connection);
		
		spitfire()->provider()->set(ConnectionManager::class, $manager);
		
		$this->model->setConnection($id);
		$this->model2->setConnection($id);
	}
	
	public function testBelongsToWhere()
	{
		$query = new QueryBuilder($this->model2);
		$model = $this->model->withHydrate(new Record(['_id' => 1, 'my_stick' => null]));
		
		$query->where('test', $model);
		$query->all();
		
		$this->assertStringContainsString("`test_id` = '1'", $this->logger->getRecords()[0]['message']);
	}
	
	public function testBelongsToWhereHas()
	{
		$handler = new TestHandler();
		$query = new QueryBuilder($this->model2);
		
		$query->whereHas('test', function (QueryBuilder $query) {
			$query->where('my_stick', 'is better than bacon');
		});
		
		$query->all();
		
		$this->assertCount(1, $handler->getRecords());
		$this->assertStringContainsString('`_id` FROM `test`', $handler->getRecords()[0]['message']);
		$this->assertStringContainsString("`.`my_stick` = 'is better than bacon' AND", $handler->getRecords()[0]['message']);
	}
}
