<?php namespace spitfire\storage\database\drivers\test;

use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\grammar\QueryGrammarInterface;
use spitfire\storage\database\grammar\RecordGrammarInterface;
use spitfire\storage\database\grammar\SchemaGrammarInterface;

/**
 * MySQL driver via PDO. This driver does <b>not</b> make use of prepared
 * statements, prepared statements become too difficult to handle for the driver
 * when using several JOINs or INs. For this reason the driver has moved from
 * them back to standard querying.
 */
abstract class AbstractDriver implements DriverInterface
{
	
	
	public function getDefaultQueryGrammar():? QueryGrammarInterface
	{
		return null;
	}
	
	public function getDefaultRecordGrammar():? RecordGrammarInterface
	{
		return null;
	}
	
	public function getDefaultSchemaGrammar():? SchemaGrammarInterface
	{
		return null;
	}
}
