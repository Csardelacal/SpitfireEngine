<?php namespace tests\spitfire\model\relations;

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
use spitfire\collection\Collection;
use spitfire\model\ActiveRecord;
use spitfire\model\attribute\HasMany as AttributeHasMany;
use spitfire\model\attribute\Table;
use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\ReflectionModel;
use spitfire\model\relations\HasMany;
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

class HasManyTest extends TestCase
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
		
		$model = new #[Table('test')] class(self::connection()) extends Model
		{
			
			private $_id;
			private $test;
			
			#[AttributeHasMany(TestModel::class, 'test')]
			private TestModel $remote;
			
			public function setTest(TestModel $t)
			{
				$this->test = $t;
			}
		};
		
		$reflection = new ReflectionModel($model::class);
		
		$record = new ActiveRecord(self::$connection, $reflection, new Record(['_id' => 1, 'test' => 1]));
		$query = $reflection->getRelationShips()['remote']->newInstance()->startQueryBuilder($record);
		
		$query->first();
		$queries = self::$connection->getAdapter()->getDriver()->queries;
		$this->assertStringContainsString(".`test` = '1'", $queries[0]);
	}
	
	/**
	 * This test focuses on resolveAll, which is the main method of eagerly loading
	 * belongstoone relationships and the one we should be using to fetch these relationships.
	 */
	public function testResolveAll()
	{
		
		
		$model = new #[Table('test2')] class(self::connection()) extends Model
		{
			
			private $_id;
			private $test;
			
			#[AttributeHasMany(TestModel::class, 'test')]
			private TestModel $remote;
			
			public function setTest(TestModel $t)
			{
				$this->test = $t;
			}
		};
		
		$reflection = new ReflectionModel($model::class);
		
		$records = Collection::fromArray([
			new ActiveRecord(self::$connection, $reflection, new Record(['_id' => 1, 'test' => 1])),
			new ActiveRecord(self::$connection, $reflection, new Record(['_id' => 2, 'test' => 2])),
			new ActiveRecord(self::$connection, $reflection, new Record(['_id' => 3, 'test' => 3])),
			new ActiveRecord(self::$connection, $reflection, new Record(['_id' => 1, 'test' => 1])),
			new ActiveRecord(self::$connection, $reflection, new Record(['_id' => 5, 'test' => 5])),
		]);
		
		$reflection->getRelationShips()['remote']->newInstance()->resolveAll($records);
		
		$queries = self::$connection->getAdapter()->getDriver()->queries;
		$this->assertStringContainsString(".`test` = '1' OR", $queries[0]);
		$this->assertStringContainsString(".`test` = '2' OR", $queries[0]);
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
			
			$migrator->add('test2', function (TableMigrationExecutor $t) {
				$t->id();
				$t->int('test', true);
			});
			
			$migrator->add('test', function (TableMigrationExecutor $t) {
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
