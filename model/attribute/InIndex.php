<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * This attribute indeicates that a property is part of an index. Properties that share
 * an index by name, will be combined.
 *
 * Properties with the highest priority will be set to be at the beginning of the index.
 * Please note that most DBMS will only have performance gains derived from querying a
 * column when including restrictions for all higher priority items.
 *
 * For example, an index(a,b) will boost the performance of queries including a, and a and b,
 * but not of queries including only restrictions for b.
 *
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class InIndex
{
	
	private string $name;
	
	private int $priority;
	
	/**
	 * This property is used as a transport when the system needs to connect this
	 * attribute to the name of the property it is attached to.
	 */
	private string $context;
	
	public function __construct(string $name, int $priority = 0)
	{
		$this->name = $name;
		$this->priority = $priority;
	}
	
	
	/**
	 * Get the value of name
	 */
	public function getName() : string
	{
		return $this->name;
	}
	
	/**
	 * Get the value of priority
	 */
	public function getPriority() : int
	{
		return $this->priority;
	}
	
	public function withContext(string $prop) : InIndex
	{
		$copy = clone $this;
		$copy->context = $prop;
		return $copy;
	}
	
	public function getContext() : string
	{
		return $this->context;
	}
}
