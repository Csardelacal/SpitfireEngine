<?php namespace spitfire\storage\database;

use Exception;
use InvalidArgumentException;
use Reference;
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
		
		if ($params === 3) { list($operator, $value) = [$value, $_]; }
		else               { $operator = '='; }
		
		try {
			#If the name of the field passed is a physical field we just use it to 
			#get a queryField
			$field = $fieldname instanceof QueryField? $fieldname : $this->getQuery()->getTable()->getField($fieldname);
			$restriction = $this->getQuery()->restrictionInstance($this->getQuery()->queryFieldInstance($field), $value, $operator);
			
		} catch (Exception $e) {
			#Otherwise we create a complex restriction for a logical field.
			$field = $this->getQuery()->getTable()->getModel()->getField($fieldname);
			
			if ($fieldname instanceof Reference && $fieldname->getTarget() === $this->getQuery()->getTable()->getModel())
			{ $field = $fieldname; }
			
			#If the fieldname was not null, but the field is null - it means that the system could not find the field and is kicking back
			if ($field === null && $fieldname!== null) { throw new PrivateException('No field ' . is_object($fieldname)? get_class($fieldname) : $fieldname, 1602231949); }
			
			$restriction = $this->getQuery()->compositeRestrictionInstance($field, $value, $operator);
		}
		
		parent::push($restriction);
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
			$this->putRestriction($copy);
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
	
	public function filterCompositeRestrictions() {
		$restrictions = $this->toArray();
		
		foreach ($restrictions as $r) {
			if ($r instanceof CompositeRestriction) {	$this->removeRestriction($r); }
			if ($r instanceof RestrictionGroup)     { $r->filterCompositeRestrictions(); }
		}
	}
	
	public function filterSimpleRestrictions() {
		$restrictions = $this->toArray();
		
		foreach ($restrictions as $r) {
			if ($r instanceof Restriction)      { $this->removeRestriction($r); }
			if ($r instanceof RestrictionGroup) { $r->filterSimpleRestrictions(); }
		}
	}
	
	public function filterEmptyGroups() {
		$restrictions = $this->toArray();
		
		foreach ($restrictions as $r) {
			if ($r instanceof RestrictionGroup) { $r->filterSimpleRestrictions(); }
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
		
		foreach ($this->restrictions as $restriction) { $restriction->setQuery($query);}
	}
	
	public function setParent(RestrictionGroup$query) {
		$this->parent = $query;
		
		foreach ($this->restrictions as $restriction) { $restriction->setParent($query);}
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
	
	/**
	 * This is the equivalent of makeExecutionPlan on the root query for any subquery.
	 * Since subqueries are logical root queries and can be executed just like
	 * normal ones they require an equivalent method that is named differently.
	 * 
	 * It retrieves all the subqueries that are needed to be executed on a relational
	 * DB before the main query.
	 * 
	 * We could have used a single method with a flag, but this way seems cleaner
	 * and more hassle free than otherwise.
	 * 
	 * @return Query[]
	 */
	public function getPhysicalSubqueries() {
		$_ret = Array();
		
		foreach ($this->getRestrictions() as $r) {
			$_ret = array_merge($_ret, $r->getPhysicalSubqueries());
		}
		
		return $_ret;
	}
	
	/**
	 * When cloning a restriction group we need to ensure that the new restrictions
	 * are assigned to the parent, and not some other object.
	 * 
	 * TODO: This would be potentially much simpler if the collection provided a 
	 * walk method that would allow to modify the elements from within.
	 */
	public function __clone() {
		$restrictions = $this->getRestrictions();
		
		foreach ($restrictions as &$r) { 
			$r = clone $r; 
			$r->setParent($this);
		}
		
		$this->reset()->add($restrictions);
	}

	abstract public function __toString();
}
