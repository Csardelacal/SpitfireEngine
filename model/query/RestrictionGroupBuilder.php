<?php namespace spitfire\model\query;

use BadMethodCallException;
use spitfire\model\QueryBuilderInterface;
use spitfire\model\relations\RelationshipInterface;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
use spitfire\storage\database\query\RestrictionGroup;

class RestrictionGroupBuilder implements RestrictionGroupBuilderInterface
{
	
	private $table;
	
	/**
	 *
	 * @var RestrictionGroup
	 */
	private $restrictionGroup;
	
	public function __construct(TableIdentifierInterface $table, RestrictionGroup $restrictionGroup)
	{
		$this->table = $table;
		$this->restrictionGroup = $restrictionGroup;
	}
	
	
	public function where(...$args) : self
	{
		switch (count($args)) {
			case 2:
				$field = $args[0];
				$operator = '=';
				$value = $args[1];
				break;
			case 3:
				$field = $args[0];
				$operator = $args[1];
				$value = $args[2];
				break;
			default:
				throw new BadMethodCallException('Invalid argument count for where', 2202231731);
		}
		
		$this->restrictionGroup->where($this->table->getOutput($field), $operator, $value);
		return $this;
	}
	
	public function getDBRestrictions()
	{
		return $this->restrictionGroup;
	}
}
