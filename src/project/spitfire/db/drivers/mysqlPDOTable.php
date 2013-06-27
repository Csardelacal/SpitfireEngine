<?php

namespace spitfire\storage\database\drivers;

use spitfire\storage\database\Table;
use spitfire\storage\database\Query;
use spitfire\storage\database\DBField;
use spitfire\model\Field;
use databaseRecord;
use Exception;

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

	/**
	 * Creates a new MySQL PDO Field object.
	 * 
	 * @param \spitfire\storage\database\Table $t
	 * @param \spitfire\model\Field $data
	 * @return \spitfire\storage\database\drivers\mysqlPDOField
	 */
	public function getFieldInstance(Field$field, $name, DBField$references = null) {
		return new mysqlPDOField($field, $name, $references);
	}

	public function repair() {
		$stt = "DESCRIBE $this";
		$fields = $this->getFields();
		//Fetch the DB Fields and create on error.
		try {
			$query = $this->getDb()->execute($stt, false);
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

	public function getQueryInstance() {
		return new MysqlPDOQuery($this);
	}
	
	/**
	 * Deletes this record from the database. This method's call cannot be
	 * undone. <b>[NOTICE]</b>Usually Spitfire will cascade all the data
	 * related to this record. So be careful calling it deliberately.
	 * 
	 * @param databaseRecord $record Database record to be deleted from the DB.
	 * 
	 */
	public function delete(databaseRecord$record) {
		$table = $this;
		$db    = $table->getDb();
		$restrictions = $record->getUniqueRestrictions();
		
		$stt = sprintf('DELETE FROM %s WHERE %s',
			$table,
			implode(' AND ', $restrictions)
			);
		$db->execute($stt);
	}

	/**
	 * Modifies this record on high write environments. If two processes modify
	 * this record simultaneously they won't generate unconsistent data.
	 * This function is especially useful for counters i.e. pageviews, clicks,
	 * plays or any kind of transactions.
	 * 
	 * @throws privateException If the database couldn't handle the request.
	 * @param databaseRecord $record Database record to be modified.
	 * @param string $key
	 * @param int|float|double $diff
	 */
	public function increment(databaseRecord$record, $key, $diff = 1) {
		
		$table = $this;
		$db = $table->getDb();
		
		$stt = sprintf('UPDATE %s SET `%s` = `%s` + %s WHERE %s',
			$table, 
			$key,
			$key,
			$db->quote($diff),
			implode(' AND ', $record->getUniqueRestrictions())
		);
		
		$db->execute($stt);
	}

	public function insert(databaseRecord$record) {
		$data = $record->getData();
		$table = $record->getTable();
		$db = $table->getDb();
                
		foreach ($data as $field => $value) {
			if ($value instanceof databaseRecord) {
				$primary = $value->getPrimaryData();
				foreach ($primary as $key => $v) {
					$data[$field . '_' . $key] = $v;
				}
				unset($data[$field]);
			}
			elseif (is_array($value)) {
				unset($data[$field]);
			}
		}
		
		$fields = array_keys($data);
		
		$quoted = array_map(Array($db, 'quote'), $data);
		
		$stt = sprintf('INSERT INTO %s (%s) VALUES (%s)',
			$table,
			implode(', ', $fields),
			implode(', ', $quoted)
			);
		$db->execute($stt);
		return $db->getConnection()->lastInsertId();
	}

	public function update(databaseRecord$record) {
		$data = $record->getData();
		$table = $record->getTable();
		$db = $table->getDb();
		
		foreach ($data as $field => $value) {
			if ($value instanceof Query) {
				if ($this->getModel()->getField($field) instanceof \ChildrenField) 
					unset($data[$field]);
				else 
					$value = $value->fetch();
			}
			
			if ($value instanceof databaseRecord) {
				$primary = $value->getPrimaryData();
				foreach ($primary as $key => $v) {
					$data[$field . '_' . $key] = $v;
				}
				unset($data[$field]);
			}
			elseif (is_array($value)) {
				unset($data[$field]);
			}
		}
		
		$quoted = Array();
		foreach ($data as $f => $v) $quoted[] = "{$table->getField($f)} = {$db->quote($v)}";
		
		$stt = sprintf('UPDATE %s SET %s WHERE %s',
			$table, 
			implode(', ', $quoted),
			implode(' AND ', $record->getUniqueRestrictions())
		);
		
		$this->getDb()->execute($stt);
		
	}

	public function restrictionInstance(DBField$field, $value, $operator = null) {
		return new MysqlPDORestriction($field, $value, $operator);
	}

	public function queryInstance($table) {
		if (!$table instanceof Table) throw new \privateException('Need a table object');
		
		return new MysqlPDOQuery($table);
	}

	public function destroy() {
		$this->getDb()->execute('DROP TABLE ' . $this);
	}
}