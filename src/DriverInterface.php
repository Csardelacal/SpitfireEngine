<?php namespace spitfire\storage\database;


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
	 * Drivers will receive a series of developer defined settings that will be
	 * used to determine how the DBMS should behave to satisfy the needs of the
	 * application.
	 * 
	 * @param Settings $settings Parameters passed to the database
	 */
	public function __construct(Settings$settings);
	
	/**
	 * Executes a migration operation on the database. This allows you to create,
	 * upgrade or downgrade database schemas.
	 */
	public function migrate(MigrationOperationInterface $migration);
	
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
	 * @param Record $record
	 */
	public function update(Record $record) : bool;
	
	/**
	 * Insert database data in the DBMS. The result is true if the data was written
	 * successfuly.
	 * 
	 * @param Record $record
	 */
	public function insert(Record $record) : bool;
	
	/**
	 * Delete database data in the DBMS. The result is true if the data was written
	 * successfuly.
	 * 
	 * @param Record $record
	 */
	public function delete(Record $record) : bool;
	
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

}
