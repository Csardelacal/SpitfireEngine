<?php namespace tests\spitfire\storage\db;

use PHPUnit\Framework\TestCase;
use spitfire\storage\database\RestrictionGroup;

class RestrictionGroupTest extends TestCase
{
	
	public function testNegate()
	{
		$group = new RestrictionGroup();
		$group->negate()->normalize();
		
		$this->assertEquals(RestrictionGroup::TYPE_AND, $group->getType());
	}
	
}