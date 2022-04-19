<?php namespace spitfire\storage\database\query;

use Closure;
use InvalidArgumentException;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\identifiers\IdentifierInterface;

/**
 * A restriction group contains a set of restrictions (or restriction groups)
 * that can be used by the database to generate more complex queries.
 *
 * This groups can be different of two different types, they can be 'OR' or 'AND',
 * changing the behavior of the group by making it more or less restrictive. This
 * OR and AND types are known from most DBMS.
 *
 * @extends Collection<RestrictionInterface>
 */
class RestrictionGroup extends Collection implements RestrictionInterface
{
	const TYPE_OR  = 'OR';
	const TYPE_AND = 'AND';
	
	/**
	 * Determines whether the restrictions within this are inclusive or exclusive. If the
	 * group is an and, all the conditions must be satisfied. If it's an or, just one has
	 * to be satisfied.
	 *
	 * @var string
	 */
	private $type = self::TYPE_AND;
	
	/**
	 * The negated flag allows the application to maintain a state on the restriction
	 * group. Negating these groups is expensive, since we have to descend into the
	 * children and rewrite a lot of stuff.
	 *
	 * Instead we maintaing the state and flip the group only when and if needed.
	 *
	 * @var bool
	 */
	private $negated = false;
	
	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @param IdentifierInterface $field
	 * @param mixed  $value
	 * @param string $_
	 * @return RestrictionGroup
	 * @throws ApplicationException
	 */
	public function where($field, $value, $_ = null) : RestrictionGroup
	{
		$params = func_num_args();
		
		/*
		 * Depending on how the parameters are provided, where will appropriately
		 * shuffle them to make them look correctly.
		 */
		if ($params === 3) {
			list($operator, $value) = [$value, $_];
		}
		else {
			$operator = Restriction::EQUAL_OPERATOR;
		}
		
		
		$this->push(new Restriction($field, $operator, $value));
		return $this;
	}
	
	/**
	 * @param string $type
	 * @return RestrictionGroup
	 */
	public function group($type = self::TYPE_OR) : RestrictionGroup
	{
		#Create the group and set the type we need
		$group = new RestrictionGroup();
		$group->setType($type);
		
		#Add it to our restriction list
		$this->push($group);
		return $group;
	}
	
	/**
	 *
	 * @param string $type
	 * @return RestrictionGroup
	 */
	public function setType(string $type) : RestrictionGroup
	{
		if ($type === self::TYPE_AND || $type === self::TYPE_OR) {
			$this->type = $type;
			return $this;
		}
		else {
			throw new InvalidArgumentException("Restriction groups can only be of type AND or OR");
		}
	}
	
	public function getType() : string
	{
		return $this->type;
	}
	
	public function negate() : RestrictionGroup
	{
		$this->negated = !$this->negated;
		return $this;
	}
	
	/**
	 * When a restriction group is flipped, the system will change the type from
	 * AND to OR and viceversa. When doing so, all the restrictions are negated.
	 *
	 * This means that <code>$a == $a->flip()</code> even though they have inverted
	 * types. This is specially interesting for query optimization and negation.
	 *
	 * @return RestrictionGroup
	 */
	public function flip() : RestrictionGroup
	{
		$this->negated = !$this->negated;
		$this->type = $this->type === self::TYPE_AND? self::TYPE_OR : self::TYPE_AND;
		
		foreach ($this as $restriction) {
			if ($restriction instanceof Restriction ||
				$restriction instanceof RestrictionGroup) {
				$restriction->negate();
			}
		}
		
		return $this;
	}
}
