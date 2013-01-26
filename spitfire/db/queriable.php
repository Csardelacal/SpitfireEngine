<?php

namespace spitfire\storage\database;

/**
 * Convenience class meant to separate tables and the table related actions from
 * their query related tasks. It will only return different query short-hand methods
 * that help speeding simple queries.
 * 
 * @package Spitfire.storage.database
 * @author César de la Cal <cesar@magic3w.com>
 * @abstract
 */
abstract class Queriable {
	
	
	/**
	 * Creates a new query for this DB Table. 
	 * 
	 * @return _SF_DBQuery
	 */
	public function startQuery() {
		
		$query = new _SF_DBQuery($this);
		return $query;
	}
	
	/**
	 * Creates a simple query with a simple restriction applied to it. This
	 * is especially useful for id related queries.
	 * 
	 * @param String $field
	 * @param String $value
	 * @return _SF_DBQuery
	 */
	public function get($field, $value) {
		#Create the query
		$query = new _SF_DBQuery($this);
		$query->addRestriction($field, $value);
		#Return it
		return $query;
	}
	
	/**
	 * Creates an empty query that would return all data. This is a syntax
	 * friendliness oriented method as it does exactly the same as startQuery
	 * 
	 * @see _SF_Queriable::startQuery
	 * @return _SF_DBQuery
	 */
	public function getAll() {
		
		$query = new _SF_DBQuery($this);
		return $query;
	}
	
	/**
	 * 
	 * @param String $field Name of the database field to be queried.
	 * @param String $value Value we're looking for
	 * @param Boolean $fuzzy Defines whether the clause should automatically
	 *                       add %'s and replace spaces with %
	 * @return _SF_DBQuery
	 */
	public function like($field, $value, $fuzzy = false) {
		
		if ($fuzzy) {
			$value = '%' . 
				str_replace(Array('%', ' '), Array('[%]', '%'), $value) . 
				'%';
		}
		
		$query = new _SF_DBQuery($this);
		$query->addRestriction(new _SF_Restriction($field, $value, _SF_Restriction::LIKE_OPERATOR));
		return $query;
	}

	/**
	 * Creates a query searching for data that IS null.
	 * 
	 * @param String $field Name of the database column to be searched for.
	 * @return _SF_DBQuery
	 */
	public function isNull($field) {
		
		$query = new _SF_DBQuery($this);
		$query->addRestriction(new _SF_Restriction($field, NULL, ' is '));
		return $query;
	}

	/**
	 * Creates a query searching for data that IS NOT null.
	 * 
	 * @param String $field Name of the database column to be searched for.
	 * @return _SF_DBQuery
	 */
	public function isNotNull($field) {
		
		$query = new _SF_DBQuery($this);
		$query->addRestriction(new _SF_Restriction($field, NULL, ' IS NOT '));
		return $query;
	}
}