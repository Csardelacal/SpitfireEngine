<?php namespace tests\spitfire\storage\database;

use Brick\VarExporter\VarExporter;
use PHPUnit\Framework\TestCase;
use spitfire\storage\database\ForeignKey;
use spitfire\storage\database\Layout;
use spitfire\storage\database\Schema;

class SchemaTest extends TestCase
{
	
	
	public function testSerialize()
	{
		$layout = new Layout('test');
		$layout2 = new Layout('test2');
		
		$layout2->putField('test', 'int', false, true);
		$layout2->primary($layout2->getField('test'));
		
		$layout->putField('test', 'int', false, true);
		$layout->putIndex(new ForeignKey('fk', $layout->getField('test'), $layout2->getTableReference()->getOutput('test')));
		
		$schema = new Schema('test');
		$schema->putLayout($layout);
		$schema->putLayout($layout2);
		
		$result = VarExporter::export($schema);
		
		$this->assertStringContainsString('test', $result);
	}
}
