<?php namespace spitfire\model\query;

interface RestrictionGroupBuilderInterface
{
	
	/**
	 * 
	 * @param mixed $args
	 */
	public function where(...$args) : self;
}
