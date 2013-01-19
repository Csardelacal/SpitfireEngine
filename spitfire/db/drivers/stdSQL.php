<?php

abstract class _SF_stdSQLDriver
{
	/**
	 * This generates a standard WHERE statement for SQL. Remember that this
	 * assumes prepared statements to be available for your driver and
	 * therefore will return the fields replaced by a question mark (?) for
	 * the driver to be replaced.
	 * 
	 * To avoid this behaviour you need to change the way restrictions
	 * generate their output.
	 * 
	 * 
	 * @param _SF_DBTable $table
	 * @param _SF_DBQuery $query
	 * @param mixed $fields
	 * @return String the prepared query statement
	 */
	public function query(_SF_DBTable $table, _SF_DBQuery $query, $fields = null) {
		
		#Declare vars
		$rpp          = $query->getResultsPerPage();
		$offset       = ($query->getPage() - 1) * $rpp;
		
		$selectstt    = 'SELECT';
		$fields       = ($fields)? $fields : $table->getFields();
		$fromstt      = 'FROM';
		$tablename    = "`{$table->getTablename()}`";
		$wherestt     = 'WHERE';
		$restrictions = $query->getRestrictions();
		$orderstt     = 'ORDER BY';
		$order        = $query->getOrder();
		$limitstt     = 'LIMIT';
		$limit        = $offset . ', ' . $rpp;
		
		#Unset unneeded data & prepare for writing
		if (empty($fields)) {
			$fields = '*';
		}
		else {
			array_walk ($fields, Array($this, 'escapeFieldName'));
			$fields = implode(', ', $fields);
		}
		
		if (empty($restrictions)) {
			$restrictions = '1';
		}
		else {
			$restrictions = implode(' AND ', $restrictions);
		}
		
		if ($rpp < 0) {
			$limitstt = '';
			$limit    = '';
		}
		
		if (empty($order)) {
			$orderstt = '';
			$order    = '';
		}
		
		$stt = array_filter(Array( $selectstt, $fields, $fromstt, $tablename, 
		    $wherestt, $restrictions, $orderstt, $order, $limitstt, $limit));
		
		return implode(' ', $stt);
		
	}
	
	/**
	 * 
	 * Creates a SQL statement for database entry deletion. It will delete
	 * 
	 * 
	 * @param _SF_DBTable $table
	 * @param type $primaries
	 * @return type
	 */
	public function delete(_SF_DBTable $table, databaseRecord $record) {
		
		#Prepare Vars
		$deletestt    = 'DELETE FROM';
		$tablename    = "`{$table->getTablename()}`";
		$wherestt     = 'WHERE';
		$where        = implode(' AND ', $record->getUniqueRestrictions());
		
		
		#Make it one string
		$stt = array_filter(Array($deletestt, $tablename, $wherestt,
					$where));
		
		return implode(' ', $stt);
	}
	
}