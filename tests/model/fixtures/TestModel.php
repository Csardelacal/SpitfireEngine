<?php namespace tests\spitfire\model\fixtures;

use spitfire\model\attribute\InIndex;
use spitfire\model\attribute\Integer;
use spitfire\model\attribute\References;
use spitfire\model\attribute\Table;
use spitfire\model\Model;

#[Table('test')]
class TestModel extends Model
{
	
	/**
	 *
	 * @var string
	 */
	#[Integer()]
	private string $test;
	
	
	/**
	 *
	 * @var int
	 */
	#[Integer(true)]
	#[InIndex('test', 2)]
	private int $example;
	
	/**
	 *
	 * @var int
	 */
	#[Integer(true)]
	#[InIndex('test', 1)]
	private int $example2;
	
	/**
	 *
	 * @var int
	 */
	#[Integer(true)]
	#[References(ForeignModel::class)]
	private int $foreign;
	
	public function getTest(): string
	{
		return $this->test;
	}
}
