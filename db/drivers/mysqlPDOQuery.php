<?php namespace spitfire\storage\database\drivers;

use spitfire\exceptions\PrivateException;
use spitfire\model\Field;
use spitfire\storage\database\Relation;
use spitfire\storage\database\Table;
use spitfire\storage\database\Query;
use spitfire\storage\database\QueryField;
use spitfire\storage\database\QueryTable;

class MysqlPDOQuery extends Query
{
	public function execute($fields = null) {
		
		$this->setAliased(false);
		
		
		#Import tables for restrictions from remote queries
		$subqueries = $this->getPhysicalSubqueries();
		$first      = array_shift($subqueries);
		$last       = end($subqueries);
		$joins      = Array();
		
		foreach ($subqueries as $q) {
			$joins[] = sprintf('LEFT JOIN %s ON (%s)', $q->getQueryTable()->definition(), implode(' AND ', $q->getRestrictions()));
		}
		
		#Declare vars
		$rpp          = $this->getResultsPerPage();
		$offset       = ($this->getPage() - 1) * $rpp;
		
		$selectstt    = 'SELECT';
		$fromstt      = 'FROM';
		$tablename    = $first->getQueryTable()->definition();
		$wherestt     = 'WHERE';
		/** @link http://www.spitfirephp.com/wiki/index.php/Database/subqueries Information about the filter*/
		$restrictions = $this->getRestrictions();
		$orderstt     = 'ORDER BY';
		$order        = $this->getOrder();
		$groupbystt   = 'GROUP BY';
		$groupby      = $this->groupby;
		$limitstt     = 'LIMIT';
		$limit        = $offset . ', ' . $rpp;
		
		if ($fields === null) {
			$fields = $last->getQueryTable()->getFields();
			
			/*
			 * If there is subqueries we default to grouping data in a way that will
			 * give us unique records and the amount of times they appear instead
			 * of repeating them.
			 * 
			 * Example: The users followed by users I follow. Even though I cannot
			 * follow a user twice, two different users I follow can again follow
			 * the same user. A regular join would produce a dataset where the user
			 * is included twice, by adding the grouping mechanism we're excluding
			 * that behavior.
			 */
			if (!empty($subqueries)) { 
				$groupby  = $fields; 
				$fields[] = 'COUNT(*) AS __META__count';
			}
		}
		
		$join = implode(' ', $joins);
		
		#Restrictions
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
		else {
			$order = "{$order['field']} {$order['mode']}";
		}
		
		if (empty($groupby)) {
			$groupbystt = '';
			$groupby    = '';
		}
		else {
			$groupby = implode(', ', $groupby);
		}
		
		$stt = array_filter(Array( $selectstt, implode(', ', $fields), $fromstt, $tablename, $join, 
		    $wherestt, $restrictions, $groupbystt, $groupby, $orderstt, $order, $limitstt, $limit));
		
		return new mysqlPDOResultSet($this->getTable(), $this->getTable()->getDb()->execute(implode(' ', $stt)));
		
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20171110
	 * @param type $parent
	 * @return \spitfire\storage\database\drivers\MysqlPDORestrictionGroup
	 */
	public function restrictionGroupInstance($parent = null) {
		return new MysqlPDORestrictionGroup($parent? : $this);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20171110
	 * @param QueryField $field
	 * @param type $value
	 * @param type $operator
	 * @return \spitfire\storage\database\drivers\MysqlPDORestriction
	 */
	public function restrictionInstance(QueryField$field, $value, $operator) {
		return new MysqlPDORestriction($this, $field, $value, $operator);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20171110
	 * @param QueryField $field
	 * @return \spitfire\storage\database\drivers\MysqlPDOQueryField|QueryField
	 */
	public function queryFieldInstance($field) {
		trigger_error('Deprecated: mysqlPDOQuery::queryFieldInstance is deprecated', E_USER_DEPRECATED);
		
		if ($field instanceof QueryField) {return $field; }
		return new MysqlPDOQueryField($this, $field);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20171110
	 * @param type $table
	 * @return \spitfire\storage\database\drivers\MysqlPDOQueryTable
	 * @throws PrivateException
	 */
	public function queryTableInstance($table) {
		if ($table instanceof Relation) { $table = $table->getTable(); }
		if ($table instanceof QueryTable) { $table = $table->getTable(); }
		
		
		if (!$table instanceof Table) { throw new PrivateException('Did not receive a table as parameter'); }
		
		return new MysqlPDOQueryTable($this, $table);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20171110
	 */
	public function compositeRestrictionInstance(Field $field = null, $value, $operator) {
		return new mysqlpdo\CompositeRestriction($this, $field, $value, $operator);
	}

	public function delete() {
		
		
		$this->setAliased(false);
		
		#Declare vars
		$selectstt    = 'DELETE';
		$fromstt      = 'FROM';
		$tablename    = $this->getTable();
		$wherestt     = 'WHERE';
		/** @link http://www.spitfirephp.com/wiki/index.php/Database/subqueries Information about the filter*/
		$restrictions = array_filter($this->getRestrictions(), Array('spitfire\storage\database\Query', 'restrictionFilter'));
		
		
		#Import tables for restrictions from remote queries
		$subqueries = $this->getPhysicalSubqueries();
		$joins      = Array();
		
		foreach ($subqueries as $q) {
			$joins[] = sprintf('LEFT JOIN %s ON (%s)', $q->getQueryTable()->definition(), implode(' AND ', $q->getRestrictions()));
		}
		
		$join = implode(' ', $joins);
		
		#Restrictions
		if (empty($restrictions)) {
			$restrictions = '1';
		}
		else {
			$restrictions = implode(' AND ', $restrictions);
		}
		
		$stt = array_filter(Array( $selectstt, $fromstt, $tablename, $join, 
		    $wherestt, $restrictions));
		
		$this->getTable()->getDb()->execute(implode(' ', $stt));
	}
}