<?php namespace tests\spitfire\storage\database;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\Aggregate;
use spitfire\storage\database\grammar\mysql\MySQLQueryGrammar;
use spitfire\storage\database\grammar\SlashQuoter;
use spitfire\storage\database\identifiers\TableIdentifier;
use spitfire\storage\database\Layout;
use spitfire\storage\database\OrderBy;
use spitfire\storage\database\Query;
use spitfire\storage\database\query\JoinTable;
use spitfire\storage\database\query\Restriction;

class MySQLQueryGrammarTest extends TestCase
{
	
	public function testSelect()
	{
		$layout  = new Layout('testtable');
		$layout->putField('testfield', 'int', false, false);
		$layout->putField('teststring', 'string:255', false, false);
		
		$grammar = new MySQLQueryGrammar(new SlashQuoter());
		$table = $layout->getTableReference();
		
		$query = new Query($table);
		$query->selectAll();
		
		$result = $grammar->selectExpression($query);
		$this->assertStringContainsString($query->getFrom()->output()->raw()[0], $result);
		$this->assertStringContainsString('testfield', $result);
		$this->assertStringContainsString('teststring', $result);
	}
	
	public function testSelectAggregate()
	{
		$layout  = new Layout('testtable');
		$layout->putField('testfield', 'int', false, false);
		$layout->putField('teststring', 'string:255', false, false);
		
		$grammar = new MySQLQueryGrammar(new SlashQuoter());
		$table = $layout->getTableReference();
		
		$query = new Query($table);
		$query->aggregate(
			$query->getFrom()->output()->getOutput('testfield'),
			new Aggregate(Aggregate::AGGREGATE_COUNT),
			'_META_COUNT_'
		);
		
		$result = $grammar->selectExpression($query);
		$this->assertStringContainsString($query->getFrom()->output()->getName(), $result);
		$this->assertStringContainsString('testfield', $result);
		$this->assertStringContainsString('_META_COUNT_', $result);
		$this->assertStringNotContainsString('teststring', $result);
	}
	
	public function testSelectJoin()
	{
		$layout  = new Layout('testtable');
		$layout->putField('testfield', 'int', false, false);
		$layout->putField('teststring', 'string:255', false, false);
		
		$layout2  = new Layout('anothertesttable');
		$layout2->putField('testfield', 'int', false, false);
		$layout2->putField('teststring', 'string:255', false, false);
		
		$grammar = new MySQLQueryGrammar(new SlashQuoter());
		$table = $layout->getTableReference();
		
		$query = new Query($table);
		$query->selectAll();
		$query->joinTable($layout2->getTableReference(), function (JoinTable $join, Query $parent) {
			$join->on($join->getOutput('testfield'), $parent->getTable()->getOutput('testfield'));
			$parent->getRestrictions()->push(new Restriction($join->getOutput('testfield'), '!=', null));
		});
		
		$result = $grammar->query($query);
		$this->assertStringContainsString($query->getTable()->raw()[0], $result);
		$this->assertStringContainsString('testfield', $result);
		$this->assertStringContainsString('teststring', $result);
	}
	
	public function testSelectExists()
	{
		$layout  = new Layout('testtable');
		$layout->putField('testfield', 'int', false, false);
		$layout->putField('teststring', 'string:255', false, false);
		
		$layout2  = new Layout('anothertesttable');
		$layout2->putField('testfield', 'int', false, false);
		$layout2->putField('teststring', 'string:255', false, false);
		
		$grammar = new MySQLQueryGrammar(new SlashQuoter());
		$table = $layout->getTableReference();
		
		$query = new Query($table);
		$query->selectAll();
		$query->whereExists(function (TableIdentifier $parent) use ($layout2) {
			$sq = new Query($layout2->getTableReference());
			$sq->select('testfield');
			$sq->where($sq->table()->getOutput('teststring'), $parent->getOutput('teststring'));
			return $sq;
		});
		
		$result = $grammar->query($query);
		
		$this->assertStringContainsString($query->getTable()->getName(), $result);
		$this->assertStringContainsString('WHERE EXISTS', $result);
		$this->assertStringContainsString('teststring', $result);
	}
	
	public function testOrderBy()
	{
		
		$layout  = new Layout('testtable');
		$layout->putField('testfield', 'int', false, false);
		$layout->putField('teststring', 'string:255', false, false);
		
		$grammar = new MySQLQueryGrammar(new SlashQuoter());
		$table = $layout->getTableReference();
		
		$query = new Query($table);
		$query->selectAll();
		$query->putOrder(new OrderBy($query->getFrom()->output()->getOutput('testfield')));
		
		$result = $grammar->query($query);
		$this->assertStringContainsString("ORDER BY `{$query->getFrom()->output()->raw()[0]}`.`testfield`", $result);
		$this->assertStringContainsString('teststring', $result);
	}
	
	public function testOrderByAggregate()
	{
		
		$layout  = new Layout('testtable');
		$layout->putField('testfield', 'int', false, false);
		$layout->putField('teststring', 'string:255', false, false);
		
		$grammar = new MySQLQueryGrammar(new SlashQuoter());
		$table = $layout->getTableReference();
		
		$query = new Query($table);
		$query->selectAll();
		
		$query->aggregate(
			$query->getFrom()->output()->getOutput('testfield'),
			new Aggregate(Aggregate::AGGREGATE_COUNT),
			'_META_COUNT_'
		);
		
		$query->putOrder(new OrderBy($query->getOutput('_META_COUNT_')->getAlias()));
		
		$result = $grammar->query($query);
		$this->assertStringContainsString($query->getFrom()->output()->raw()[0], $result);
		$this->assertStringContainsString('testfield', $result);
		$this->assertStringContainsString('ORDER BY `_META_COUNT_`', $result);
		$this->assertStringContainsString('teststring', $result);
	}
}
