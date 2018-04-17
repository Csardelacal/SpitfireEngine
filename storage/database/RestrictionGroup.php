<?php namespace spitfire\storage\database;

use InvalidArgumentException;
use spitfire\core\Collection;
use spitfire\exceptions\PrivateException;

/**
 * A restriction group contains a set of restrictions (or restriction groups)
 * that can be used by the database to generate more complex queries.
 * 
 * This groups can be different of two different types, they can be 'OR' or 'AND',
 * changing the behavior of the group by making it more or less restrictive. This
 * OR and AND types are known from most DBMS.
 */
abstract class RestrictionGroup extends Collection
{
	const TYPE_OR  = 'OR';
	const TYPE_AND = 'AND';
	
	private $parent;
	private $type = self::TYPE_OR;
	private $negated = false;
	
	public function __construct(RestrictionGroup$parent = null, $restrictions = Array() ) {
		$this->parent = $parent;
		parent::__construct($restrictions);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20170720
	 * @param type $r
	 */
	public function removeRestriction($r) {
		parent::remove($r);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20170720
	 * @param type $restriction
	 */
	public function putRestriction($restriction) {
		parent::push($restriction);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20170720
	 * @param type $restrictions
	 */
	public function setRestrictions($restrictions) {
		parent::reset();
		parent::add($restrictions);
	}
	
	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @todo This method does not accept logical fields as parameters
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @deprecated since version 0.1-dev 20170923
	 * @param string $fieldname
	 * @param mixed  $value
	 * @param string $operator
	 * @return RestrictionGroup
	 * @throws PrivateException
	 */
	public function addRestriction($fieldname, $value, $operator = '=') {
		return $this->where($fieldname, $operator, $value);
	}

	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @todo This method does not accept logical fields as parameters
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @param string $fieldname
	 * @param mixed  $value
	 * @param string $_
	 * @return RestrictionGroup
	 * @throws PrivateException
	 */
	public function where($fieldname, $value, $_ = null) {
		$params = func_num_args();
		$rm     = $this->getQuery()->getTable()->getDb()->getRestrictionMaker();
		
		/*
		 * Depending on how the parameters are provided, where will appropriately
		 * shuffle them to make them look correctly.
		 */
		if ($params === 3) { list($operator, $value) = [$value, $_]; }
		else               { $operator = '='; }
		
		parent::push($rm->make($this, $fieldname, $operator, $value));
		return $this;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20170720
	 * @param type $restrictions
	 */
	public function getRestrictions() {
		return parent::toArray();
	}
	
	public function importRestrictions(RestrictionGroup$query) {
		$restrictions = $query->getRestrictions();
		
		foreach($restrictions as $r) {
			$copy = clone $r;
			$copy->setParent($this);
			$this->push($copy);
		}
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20170720
	 * @param type $index
	 */
	public function getRestriction($index) {
		return parent::offsetGet($index);
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20170720
	 * @return type
	 */
	public function getConnectingRestrictions() {
		trigger_error('Method RestrictionGroup::getConnectingRestrictions() is deprecated', E_USER_DEPRECATED);
		$_ret = Array();
		
		foreach ($this->toArray() as $r) { $_ret = array_merge($_ret, $r->getConnectingRestrictions());}
		
		return $_ret;
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20171110
	 */
	public function filterCompositeRestrictions() {
		$restrictions = $this->toArray();
		
		foreach ($restrictions as $r) {
			if ($r instanceof CompositeRestriction) {	$this->removeRestriction($r); }
			if ($r instanceof RestrictionGroup)     { $r->filterCompositeRestrictions(); }
		}
	}
	
	/**
	 * 
	 * @deprecated since version 0.1-dev 20171110
	 */
	public function filterSimpleRestrictions() {
		$restrictions = $this->toArray();
		
		foreach ($restrictions as $r) {
			if ($r instanceof Restriction)      { $this->removeRestriction($r); }
			if ($r instanceof RestrictionGroup) { $r->filterSimpleRestrictions(); }
		}
	}
	
	/**
	 * Removes empty groups from the group. This is important since otherwise a
	 * query generator will most likely generate malformed SQL for this query.
	 */
	public function filterEmptyGroups() {
		$restrictions = $this->toArray();
		
		foreach ($restrictions as $r) {
			if ($r instanceof RestrictionGroup) { $r->filterEmptyGroups(); }
			if ($r instanceof RestrictionGroup && $r->isEmpty()) { $this->remove($r); }
		}
	}
	
	/**
	 * @param string $type
	 * @return RestrictionGroup
	 */
	public function group($type = self::TYPE_OR) {
		#Create the group and set the type we need
		$group = $this->getQuery()->restrictionGroupInstance($this);
		$group->setType($type);
		
		#Add it to our restriction list
		return $this->push($group);
	}
	
	public function endGroup() {
		return $this->parent;
	}
	
	public function setQuery(Query$query) {
		$this->parent = $query;
		return $this;
	}
	
	public function setParent(RestrictionGroup$query) {
		$this->parent = $query;
		
		return $this;
	}
	
	public function getParent() {
		return $this->parent;
	}
	
	/**
	 * As opposed to the getParent method, the getQuery method will ensure that
	 * the return is a query.
	 * 
	 * This allows the application to quickly get information about the query even
	 * if the restrictions are inside of several layers of restriction groups.
	 * 
	 * @return Query
	 */
	public function getQuery() {
		return $this->parent->getQuery();
	}
	
	public function setType($type) {
		if ($type === self::TYPE_AND || $type === self::TYPE_OR) {
			$this->type = $type;
			return $this;
		}
		else {
			throw new InvalidArgumentException("Restriction groups can only be of type AND or OR");
		}
	}
	
	public function getType() {
		return $this->type;
	}
	
	public function getSubqueries() {
		
		/*
		 * First, we extract the physical queries from the underlying queries.
		 * These queries should be executed first, to make it easy for the system
		 * to retrieve the data the query depends on.
		 */
		$_ret = Array();
		
		foreach ($this->getRestrictions() as $r) {
			$_ret = array_merge($_ret, $r->getSubqueries());
		}
		
		return $_ret;
	}
	
	public function negate() {
		$this->negated = !$this->negated;
		return $this;
	}
	
	public function normalize() {
		if (!$this->negated) {
			$this->type = $this->type === self::TYPE_AND? self::TYPE_OR : self::TYPE_AND;
			
			foreach ($this as $restriction) {
				if ($restriction instanceof Restriction) { $restriction->negate(); }
				if ($restriction instanceof CompositeRestriction) { $restriction->negate(); }
				if ($restriction instanceof RestrictionGroup) { $restriction->negate()->normalize(); }
			}
		}
		
		foreach ($this as $restriction) {
			if ($restriction instanceof RestrictionGroup) { 
				$restriction->normalize(); 
				
				if ($restriction->getType() === $this->getType()) {
					$this->add($restriction->each(function ($e) { $e->setParent($this); })->toArray());
					$this->remove($restriction);
				}
			}
		}
	}
	
	/**
	 * When cloning a restriction group we need to ensure that the new restrictions
	 * are assigned to the parent, and not some other object.
	 * 
	 * TODO: This would be potentially much simpler if the collection provided a 
	 * walk method that would allow to modify the elements from within.
	 */
	public function __clone() {
		$restrictions = $this->toArray();
		
		foreach ($restrictions as &$r) { 
			$r = clone $r; 
			$r->setParent($this);
		}
		
		$this->reset()->add($restrictions);
	}

	abstract public function __toString();
}
