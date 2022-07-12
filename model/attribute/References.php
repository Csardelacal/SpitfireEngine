<?php namespace spitfire\model\attribute;

use Attribute;

/**
 * This attribute allows a programmer to determine whether the column references another
 * column in another table. This is required for relationships to work properly and for
 * the DBMS to understand the data it contains and the relations between it.
 *
 * In most DBMS this attribute is equivalent to a foreign key. Since Spitfire does not support
 * foreign keys (as in foreign keys spanning multiple columns), we refer to this attribute
 * by a different name.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class References
{
	
	/**
	 *
	 * @var class-string
	 */
	private string $model;
	
	/**
	 *
	 * @param class-string $model
	 */
	public function __construct(string $model)
	{
		$this->model = $model;
	}
	
	/**
	 * Get the value of table
	 *
	 * @return  class-string
	 */
	public function getModel() : string
	{
		return $this->model;
	}
}
