<?php namespace spitfire\model\query;

interface RestrictionGroupBuilderInterface
{
	
	public function where(...$args) : self;
	
}
