<?php namespace spitfire\storage\database\drivers;

use spitfire\storage\database\DB;

/**
 * 
 * @deprecated since version 0.1-dev 20160902
 */
abstract class stdSQLDriver extends DB
{
	
	
	public abstract function quote($text);
	public abstract function execute($statement, $attemptrepair = true);
	
}
