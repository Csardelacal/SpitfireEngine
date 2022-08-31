<?php namespace tests\spitfire\model;

use PHPUnit\Framework\TestCase;
use spitfire\model\ActiveRecord;
use spitfire\storage\database\ConnectionGlobal;
use spitfire\storage\database\Record;
use tests\spitfire\model\fixtures\TestModel;

class HydrateTest extends TestCase
{
	
	public function testHydrating()
	{
		$record = new Record([
			'test' => 'a',
			'example' => 2
		]);
		
		$model = new TestModel(new ConnectionGlobal());
		$instance = $model->withSelfHydrate($record);
		
		$this->assertEquals('a', $instance->getTest());
	}
}
