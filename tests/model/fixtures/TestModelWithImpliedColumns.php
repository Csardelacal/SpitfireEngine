<?php namespace tests\spitfire\model\fixtures;

use spitfire\model\attribute\Id;
use spitfire\model\attribute\InIndex;
use spitfire\model\attribute\Integer;
use spitfire\model\attribute\References;
use spitfire\model\attribute\SoftDelete;
use spitfire\model\attribute\Table;
use spitfire\model\attribute\Timestamps;
use spitfire\model\Model;
use spitfire\model\traits\WithId;
use spitfire\model\traits\WithSoftDeletes;
use spitfire\model\traits\WithTimestamps;

#[Table('test_implied')]
#[Id]
#[SoftDelete]
#[Timestamps]
class TestModelWithImpliedColumns extends Model
{
	
	use WithSoftDeletes, WithTimestamps, WithId;
	
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
}
