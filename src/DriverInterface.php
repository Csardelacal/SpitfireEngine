<?php namespace spitfire\storage\database;

use spitfire\storage\database\grammar\RecordGrammarInterface;
use spitfire\storage\database\grammar\QueryGrammarInterface;
use spitfire\storage\database\grammar\SchemaGrammarInterface;
use spitfire\storage\database\query\ResultInterface;

/**
 * The driver should allow the application to perform the following operations:
 *
 * - Query data from the database (using a query object)
 * - Insert data into the database
 * - Update data in the database
 * - Make changes to the schema (by injecting migration objects)
 *
 * We should understand the driver as an API endpoint to the interface of an abstract
 * DBMS that provides select, insert, update and alter type SQL statements (or compatible)
 * which introduces the abstraction from the specific database engine in the backend.
 *
 * This means that the application becomes completely database agnostic.
 *
 * This abstraction incurs the cost of rigidity. Since we only address features that
 * are very common among all database engines, we do not have all the features available
 * to us. Making specialized features harder to implement.
 *
 * @author César de la Cal <cesar@magic3w.com>
 */
interface DriverInterface
{
	
	public function getDefaultSchemaGrammar() :? SchemaGrammarInterface;
	public function getDefaultRecordGrammar() :? RecordGrammarInterface;
	public function getDefaultQueryGrammar() :? QueryGrammarInterface;
	
	/**
	 *
	 * @param string $sql
	 * @return int
	 */
	public function write(string $sql) : int;
	
	/**
	 *
	 * @param string $sql
	 * @return ResultInterface
	 */
	public function read(string $sql) : ResultInterface;
	
	public function lastInsertId() : string|false;
}
