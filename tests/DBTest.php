<?php namespace tests\spitfire\storage\db;

use PHPUnit\Framework\TestCase;
use spitfire\core\Environment;
use spitfire\storage\database\drivers\mysqlpdo\Driver;
use spitfire\storage\database\Settings;
use function db;

class DBTest extends TestCase
{
	
	public function testTableCache() {
		$db = new Driver(Settings::fromArray([]));
		$db->table(new \spitfire\model\Schema('test'));
		
		$tc = $db->getTableCache();
		$this->assertInstanceOf(\spitfire\storage\database\Table::class, $tc->get('test'));
	}
		
}
