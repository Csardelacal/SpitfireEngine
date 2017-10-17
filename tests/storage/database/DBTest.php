<?php namespace tests\spitfire\storage\db;

use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
	
	public function testdb() {
		$this->assertInstanceOf('spitfire\storage\database\DB', db());
	}
	
	
}