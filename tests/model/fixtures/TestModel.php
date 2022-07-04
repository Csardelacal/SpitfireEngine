<?php namespace tests\spitfire\model\fixtures;

use spitfire\model\attribute\Column;
use spitfire\model\attribute\InIndex;
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
	#[Column('string:255')]
	private string $test;
	
	
	/**
	 *
	 * @var int
	 */
	#[Column('int:unsigned')]
	#[InIndex('test', 2)]
	private int $example;
	
	/**
	 *
	 * @var int
	 */
	#[Column('int:unsigned')]
	#[InIndex('test', 1)]
	private int $example2;
	
	/**
	 * 
	 * @var int
	 */
	#[References('another')]
	private int $foreign;
}
