<?php namespace spitfire\storage\database\tests\grammar\mysql;

use PDO;
use PHPUnit\Framework\TestCase;
use spitfire\collection\Collection;
use spitfire\storage\database\drivers\mysqlpdo\TableMigrationExecutor;
use spitfire\storage\database\Field;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\mysql\MySQLTableGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\Index;

class MySQLTableGrammarTest extends TestCase
{
	
	/**
	 * This test simulates the behavior of TableMigrationExecutor::increments,
	 * making sure that the system can add a column and an index at the same time
	 * and according to the mysql spec.
	 *
	 * @see https://dev.mysql.com/doc/refman/8.0/en/alter-table-examples.html
	 */
	public function testAddIncrements()
	{
		
		$grammar = new MySQLTableGrammar(new MySQLQueryGrammar(new SlashQuoter));
		$field = new Field('c', 'long:unsigned', false, true);
		$index = new Index('_primary', Collection::fromArray([$field]), true, true);
		
		$sql = $grammar->alterTable(
			't2',
			[$grammar->addColumn($field), $grammar->addIndex($index)]
		);
		
		$this->assertStringContainsString('PRIMARY KEY', $sql);
		$this->assertStringContainsString('ADD COLUMN `c` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT', $sql);
	}
	
	public function testDropColumn()
	{
		$grammar = new MySQLTableGrammar(new MySQLQueryGrammar(new SlashQuoter));
		$field = new Field('c', 'long:unsigned', false, true);
		
		$sql = $grammar->alterTable(
			't2',
			[$grammar->dropColumn($field)]
		);
		
		$this->assertStringContainsString('DROP COLUMN `c`', $sql);
	}
}
