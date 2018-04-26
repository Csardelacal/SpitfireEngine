<?php namespace spitfire\storage\database\drivers\sql;

use spitfire\storage\database\Table;

abstract class SQLTable extends Table
{
	
	/**
	 * Creates the column definitions for each column
	 * 
	 * @return mixed
	 */
	protected function columnDefinitions() {
		$fields = $this->getFields();
		foreach ($fields as $name => $f) {
			$fields[$name] = '`'. $name . '` ' . $f->columnDefinition();
		}
		return $fields;
	}
}
