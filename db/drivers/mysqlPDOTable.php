<?php namespace spitfire\storage\database\drivers;


/**
 * Represents table specific properties and methods for the MySQLPDO Driver.
 */
class MysqlPDOTable extends stdSQLTable
{
	
	/**
	 * Returns the name of a table as DB Object reference (with quotes).
	 * 
	 * @return string The name of the table escaped and ready for use inside
	 *                of a query.
	 */
	public function __toString() {
		return "`{$this->tablename}`";
	}
}