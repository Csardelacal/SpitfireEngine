<?php namespace spitfire\storage\database\drivers;

use Exception;

/**
 * Represents table specific properties and methods for the MySQLPDO Driver.
 * 
 * @deprecated since version 0.1-dev 20170807
 */
class MysqlPDOTable extends sql\SQLTable
{
	
	public function repair() {
		$table = $this;
		$stt = "DESCRIBE $table";
		$fields = $table->getFields();
		//Fetch the DB Fields and create on error.
		try {
			$query = $this->getDb()->execute($stt, Array(), false);
		}
		catch(Exception $e) {
			return $this->create();
		}
		//Loop through the exiting fields
		while (false != ($f = $query->fetch())) {
			try {
				$field = $this->getField($f['Field']);
				unset($fields[$field->getName()]);
			}
			catch(Exception $e) {/*Ignore*/}
		}
		
		foreach($fields as $field) $field->add();
	}
	
	public function create() {
		
		$table = $this;
		$definitions = $table->columnDefinitions();
		$indexes     = $table->getLayout()->getIndexes();
		
		#Strip empty definitions from the list
		$clean = array_filter(array_merge($definitions, $indexes->toArray()));
		
		$stt = sprintf('CREATE TABLE %s (%s) ENGINE=InnoDB CHARACTER SET=utf8',
			$table,
			implode(', ', $clean)
			);
		
		return $table->getDb()->execute($stt);
	}
	
	public function destroy() {
		$this->getDb()->execute('DROP TABLE ' . $this->getTable());
	}
	
	/**
	 * Returns the name of a table as DB Object reference (with quotes).
	 * 
	 * @return string The name of the table escaped and ready for use inside
	 *                of a query.
	 */
	public function __toString() {
		return strval($this->getLayout());
	}
}