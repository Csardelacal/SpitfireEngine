<?php namespace tests\spitfire\storage\db;

use IntegerField;
use PHPUnit\Framework\TestCase;
use spitfire\storage\database\drivers\mysqlpdo\QueryField;
use spitfire\storage\database\drivers\mysqlpdo\QueryTable;
use spitfire\storage\database\drivers\mysqlpdo\Field as MysqlField;
use spitfire\storage\database\drivers\MysqlPDOQuery;
use spitfire\storage\database\drivers\MysqlPDORestriction;
use spitfire\storage\database\drivers\MysqlPDORestrictionGroup;
use spitfire\storage\database\Schema;
use spitfire\storage\database\Table;
use function db;

class RestrictionGroupTest extends TestCase
{
	
	/**
	 * This test creates a 
	 */
	public function testClone() {
		
		$table = new Table(db(), new Schema('test'));
		$query = new MysqlPDOQuery($table);
		$field = new MysqlField(new IntegerField(), 'test');
		$queryfield = new QueryField(new QueryTable($table), $field);
		
		$groupa = new MysqlPDORestrictionGroup($query);
		$groupa->putRestriction(new MysqlPDORestriction($groupa, $queryfield, 'A'));
		
		$groupb = clone $groupa;
		
		$this->assertEquals($groupa->getRestriction(0)->getParent() === $groupb->getRestriction(0)->getParent(), 
				  false, 'The two restrictions from two cloned queries should have different parents');
		
		$this->assertEquals($groupa->getRestriction(0)->getQuery() === $groupb->getRestriction(0)->getQuery(), 
				  true, 'The two restrictions should share a common query');
	}
	
}