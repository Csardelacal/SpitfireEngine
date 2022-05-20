<?php namespace spitfire\storage\database;

use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;

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
	
	/**
	 * Available modes for drivers. These modes can be passed as binary masks, and the drivers
	 * can choose to implement the way they wish.
	 *
	 * The EXC mode enables sending queries to the DBMS. Executing them as they are available.
	 */
	const MODE_EXC = 0x001;
	
	/**
	 * The logging mode requests the driver to send the data to a log output, please refer to the
	 * driver's manual to see how the logging is configured so it suits your needs.
	 */
	const MODE_LOG = 0x002;
	
	/**
	 * PRinT requests the queries to be printed to STDOUT. This is usually only useful for debugging
	 * purposes.
	 */
	const MODE_PRT = 0x004;
	
	/**
	 * This mode causes a breakpoint to be dispatched whenever a query is executed. This is useful when
	 * debugging a query that is misbehaving and we want to see the query being sent to the DBMS.
	 */
	const MODE_DBG = 0x008;
	
	/**
	 * Returns an object capable of making changes to the database, allowing migrations to
	 * be applied and rolled back without issue.
	 *
	 * @param Schema $schema
	 * @return SchemaMigrationExecutorInterface
	 */
	public function getMigrationExecutor(Schema $schema) : SchemaMigrationExecutorInterface;
	
	/**
	 * Allows the driver to initialize the connection / file for the DBMS to be run,
	 * it should be safe to call this function multiple times.
	 */
	public function init() : void;
	
	/**
	 * Query the database for data. The query needs to encapsulate all the data
	 * that is needed for our DBMS to execute the query.
	 *
	 * @param Query $query
	 * @return ResultSetInterface
	 */
	public function query(Query $query): ResultSetInterface;
	
	/**
	 * Update database data in the DBMS. The result is true if the data was written
	 * successfuly.
	 *
	 * @param LayoutInterface $layout
	 * @param Record $record
	 */
	public function update(LayoutInterface $layout, Record $record) : bool;
	
	/**
	 * Insert database data in the DBMS. The result is true if the data was written
	 * successfuly.
	 *
	 * @param LayoutInterface $layout
	 * @param Record $record
	 */
	public function insert(LayoutInterface $layout, Record $record) : bool;
	
	/**
	 * Delete database data in the DBMS. The result is true if the data was written
	 * successfuly.
	 *
	 * @param LayoutInterface $layout
	 * @param Record $record
	 */
	public function delete(LayoutInterface $layout, Record $record) : bool;
	
	/**
	 * Allows the application to create the database needed to store the tables
	 * and therefore data for the application. Some DBMS like SQLite won't support
	 * multiple databases - so this may not do anything.
	 *
	 * @return bool Returns whether the operation could be completed successfully
	 */
	public function create();
	
	/**
	 * Destroys the database and all of it's contents. Drivers may not allow
	 * this method to be called unless they're being operated in debug mode or
	 * a similar mode.
	 *
	 * @return bool Whether the operation could be completed
	 */
	public function destroy();
	
	/**
	 * Sets the mode for the driver to something else that the default. When invoked
	 * without parameters, it must return the current mode the driver is in.
	 *
	 * The recommended default for production is 0x001
	 * The recommended default for development and staging is 0x003
	 */
	public function mode(int $mode = null) : int;
}
