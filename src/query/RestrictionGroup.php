<?php namespace spitfire\storage\database\query;

use Closure;
use InvalidArgumentException;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
use spitfire\storage\database\Query;

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
	 * The table allows us to maintain a lst of available fields for the restriction group,
	 * making functions like whereExists available.
	 *
	 * @var TableIdentifierInterface
	 */
	private $table;
	
	public function __construct(TableIdentifierInterface $table)
	{
		$this->table = $table;
	}
	
	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @param string $field
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
		
		
		$this->push(new Restriction($this->table->getOutput($field), $operator, $value));
		return $this;
	}
	
	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @param Closure $generator
	 * @return RestrictionGroup
	 */
	public function whereExists(Closure $generator) : RestrictionGroup
	{
		$value = $generator($this->table);
		assert($value instanceof Query);
		
		$this->push(new Restriction(null, Restriction::EQUAL_OPERATOR, $value));
		return $this;
	}
	
	/**
	 * @param string $type
	 * @return RestrictionGroup
	 */
	public function group($type = self::TYPE_OR) : RestrictionGroup
	{
		#Create the group and set the type we need
		$group = new RestrictionGroup($this->table);
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
	
	public function table() : TableIdentifierInterface
	{
		return $this->table;
	}
	
}
