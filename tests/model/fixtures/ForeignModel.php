<?php namespace tests\spitfire\model\fixtures;

use spitfire\model\attribute\Integer;
use spitfire\model\attribute\Primary;
use spitfire\model\attribute\Table;
use spitfire\model\Model;

#[Table('test')]
class ForeignModel extends Model
{
	
	/**
	 *
	 * @var string
	 */
	#[Integer()]
	#[Primary()]
	private string $test;
}
