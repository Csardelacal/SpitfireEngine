<?php namespace tests\spitfire\model;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */

use PHPUnit\Framework\TestCase;
use spitfire\model\ActiveRecord;
use spitfire\model\attribute\BelongsToOne as AttributeBelongsToOne;
use spitfire\model\attribute\Table;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\ModelFactory;
use spitfire\model\query\RestrictionGroupBuilder;
use spitfire\model\QueryBuilder;
use spitfire\model\QueryBuilderBuilder;
use spitfire\model\ReflectionModel;
use spitfire\model\relations\BelongsToOne;
use spitfire\model\traits\WithSoftDeletes;
use spitfire\storage\database\Connection;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\test\AbstractDriver;
use spitfire\storage\database\drivers\test\AbstractResultSet;
use spitfire\storage\database\events\QueryBeforeCreateEvent;
use spitfire\storage\database\events\QueryBeforeEvent;
use spitfire\storage\database\events\SoftDeleteQueryListener;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLRecordGrammar;
use spitfire\storage\database\grammar\mysql\MySQLSchemaGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\Layout;
use spitfire\storage\database\query\ResultInterface;
use spitfire\storage\database\Record;
use spitfire\storage\database\Schema;

class QueryBuilderSoftDeleteTest extends TestCase
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
		$this->layout->putField('my_test', 'string:255', false, false);
		$this->layout->putField('removed', 'int', true, false);
		$this->layout->primary($this->layout->getField('_id'));
		$this->layout->events()->hook(QueryBeforeCreateEvent::class, new SoftDeleteQueryListener('removed'));
		
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
				new MySQLSchemaGrammar(new MySQLQueryGrammar(new SlashQuoter))
			)
		);
		
		$this->model = new #[Table('test')] class ($connection) extends Model {
			use WithSoftDeletes;
			
			private int $_id = 0;
			
			public function getId()
			{
				return $this->_id;
			}
		};
		
		/**
		 * PHP needs us to write the class' ID into a constant that we can use, this is
		 * just garbled data to reference that anonymous class in the next one.
		 */
		if (!defined('JKEMLkjleKKLM')) {
			define('JKEMLkjleKKLM', $this->model::class);
		}
		$this->model2 = new #[Table('test2')] class ($this->model) extends Model {
			private $parent;
			
			#[AttributeBelongsToOne(JKEMLkjleKKLM, '_id')]
			private $test;
			
			public function __construct(Model $parent)
			{
				$this->parent = $parent;
			}
			
			public function test()
			{
				return new BelongsToOne(
					new Field(new ReflectionModel($this::class), 'test_id'), 
					new Field(new ReflectionModel($this->parent::class), '_id'));
			}
			
		};
		
		$schema = new Schema('test');
		$schema->putLayout($this->layout);
		$schema->putLayout($this->layout2);
		
		spitfire()->provider()->set(Connection::class, $connection);
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
				new MySQLSchemaGrammar(new MySQLQueryGrammar(new SlashQuoter))
			)
		);
		
		$builder = (new QueryBuilder(
			$connection,
			new ReflectionModel($this->model2::class)
		))->withDefaultMapping();
		
		$builder->has('test', function (QueryBuilderBuilder $builder) : QueryBuilder {
			return $builder->where('my_stick', 'is better than bacon');
		});
		
		$connection->query($builder->getQuery());
		
		$this->assertStringContainsString('WHERE EXISTS', $driver->queries[0]);
		$this->assertStringContainsString('`my_stick` = \'is better than bacon\'', $driver->queries[0]);
		$this->assertStringContainsString('`removed` IS NULL', $driver->queries[0]);
		$this->assertCount(1, $driver->queries);
		$this->assertStringContainsString('`_id` FROM `test`', $driver->queries[0]);
		$this->assertStringContainsString("`.`my_stick` = 'is better than bacon' AND", $driver->queries[0]);
	}
	
	/**
	 * Test whether the query builder can add restrictions to the query.
	 */
	public function testWhere()
	{
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([
					['_id' => 1, 'my_stick' => '', 'my_test' => '', 'removed' => null]
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
			$this->schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter()),
				new MySQLSchemaGrammar(new MySQLQueryGrammar(new SlashQuoter))
			)
		);
		
		$model = new #[Table('test')] class ($connection) extends Model {
			private int $_id = 0;
			private string $my_stick;
			private string $my_test;
			
			public function getId()
			{
				return $this->_id;
			}
			
		};
		
		$builder = (new QueryBuilder(
			$connection,
			new ReflectionModel($model::class)
		))->withDefaultMapping();
		
		$where = $builder->where('_id', 1);
		$restrictions = $builder->getQuery()->getRestrictions();
		
		$this->assertInstanceOf(QueryBuilder::class, $where);
		$this->assertEquals(2, $restrictions->restrictions()->count());
	}
	
	/**
	 * Tests the withTrashed and onlyTrashed shorthands for the query builder
	 */
	public function testModelFactoryShortHands()
	{
		
		$driver = new class extends AbstractDriver {
			public $queries = [];
			
			public function read(string $sql): ResultInterface
			{
				$this->queries[] = $sql;
				return new AbstractResultSet([
					['_id' => 1, 'my_stick' => '', 'my_test' => '', 'removed' => null]
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
			$this->schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter()),
				new MySQLSchemaGrammar(new MySQLQueryGrammar(new SlashQuoter))
			)
		);
		
		$model = new #[Table('test')] class ($connection) extends Model {
			use WithSoftDeletes;
			
			private int $_id = 0;
			private string $my_stick;
			private string $my_test;
			
			public function getId()
			{
				return $this->_id;
			}
			
		};
		
		$factory = new ModelFactory($connection);
		
		# 1. Only the not trashed
		$driver->queries = [];
		$builder = $factory->from($model::class);
		$builder->all();
		$sql = $driver->queries[0];
		$this->assertStringContainsString("`removed` IS NULL", $sql);
		
		# 2. Only the trashed
		$driver->queries = [];
		$factory->from($model::class)->onlyTrashed()->all();
		$sql = $driver->queries[0];
		$this->assertStringContainsString("`removed` IS NOT NULL", $sql);
		
		# 3. All of them
		$driver->queries = [];
		$builder = $factory->from($model::class)->withTrashed();
		$builder->all();
		$sql = $driver->queries[0];
		$this->assertStringNotContainsString("`removed` IS", $sql);
	}
	
	/**
	 * Test whether the query builder can properly perform quick counts.
	 */
	public function testQuickCount()
	{
		
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
			$this->schema,
			new Adapter(
				$driver,
				new MySQLQueryGrammar(new SlashQuoter()),
				new MySQLRecordGrammar(new SlashQuoter()),
				new MySQLSchemaGrammar(new MySQLQueryGrammar(new SlashQuoter))
			)
		);
		
		$model = new #[Table('test')] class ($connection) extends Model {
			private int $_id = 0;
			private string $my_stick;
			private string $my_test;
			
			public function getId()
			{
				return $this->_id;
			}
		};
		
		$builder = (new QueryBuilder(
			$connection,
			new ReflectionModel($model::class)
		))->withDefaultMapping();
		
		$builder->where('_id', 1)->quickCount();
		$this->assertStringMatchesFormat(
			'SELECT count(`_id`) AS `c` FROM (SELECT `test_%d`.`_id` '.
			'FROM `test` AS `test_%d` WHERE `test_%d`.`removed` IS NULL AND '.
			'`test_%d`.`_id` = \'1\' LIMIT 0, 101) AS `t_%d` WHERE 1',
			$driver->queries[0]
		);
	}
	
}
