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
	 * @var string
	 */
	private string $table;
	
	/**
	 *
	 * @var string
	 */
	private ?string $column;
	
	/**
	 *
	 * @param string $table
	 * @param string|null $column
	 */
	public function __construct(string $table, string $column = null)
	{
		$this->table = $table;
		$this->column = $column;
	}
	
	/**
	 * Get the value of table
	 *
	 * @return  string
	 */
	public function getTable() : string
	{
		return $this->table;
	}
	
	/**
	 * Get the value of column. If the value is null, the system should determine the
	 * primary key of the remote table.
	 *
	 * @return  string|null
	 */
	public function getColumn() :? string
	{
		return $this->column;
	}
}
