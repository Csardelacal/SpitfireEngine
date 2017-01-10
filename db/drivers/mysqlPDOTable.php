<?php namespace spitfire\storage\database\drivers;

use Exception;

/**
 * Represents table specific properties and methods for the MySQLPDO Driver.
 */
class MysqlPDOTable extends stdSQLTable
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
		$foreignkeys = $table->foreignKeyDefinitions();
		$pk = $table->getPrimaryKey();
		
		foreach($pk as &$f) { $f = '`' . $f->getName() .  '`'; }
		
		if (!empty($foreignkeys)) $definitions = array_merge ($definitions, $foreignkeys);
		
		if (!empty($pk)) $definitions[] = 'PRIMARY KEY(' . implode(', ', $pk) . ')';
		
		#Strip empty definitions from the list
		$clean = array_filter($definitions);
		
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
		return "`{$this->tablename}`";
	}
}