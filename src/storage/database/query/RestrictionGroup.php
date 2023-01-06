<?php namespace spitfire\storage\database\query;

use BadMethodCallException;
use InvalidArgumentException;
use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\identifiers\FieldIdentifier;
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
 * @mixin Collection<Restriction|RestrictionGroup>
 */
class RestrictionGroup
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
	
	/**
	 * The table allows us to maintain a lst of available fields for the restriction group,
	 * making functions like whereExists available.
	 *
	 * @var Collection<Restriction|RestrictionGroup>
	 */
	private $restrictions;
	
	public function __construct(TableIdentifierInterface $table)
	{
		$this->table = $table;
		$this->restrictions = new Collection();
	}
	
	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @param FieldIdentifier $field
	 * @param mixed $value
	 * @param mixed $_
	 * @return RestrictionGroup
	 * @throws ApplicationException
	 */
	public function where(FieldIdentifier $field, $value, $_ = null) : RestrictionGroup
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
		
		
		$this->restrictions->push(new Restriction($field, $operator, $value));
		return $this;
	}
	
	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @param callable(TableIdentifierInterface):Query $generator
	 * @return RestrictionGroup
	 */
	public function whereExists($generator) : RestrictionGroup
	{
		$value = $generator($this->table);
		assert($value instanceof Query);
		
		$this->restrictions->push(new Restriction($value, Restriction::EQUAL_OPERATOR, null));
		return $this;
	}
	
	/**
	 * Adds a restriction to the current query. Restraining the data a field
	 * in it can contain.
	 *
	 * @see  http://www.spitfirephp.com/wiki/index.php/Method:spitfire/storage/database/Query::addRestriction
	 *
	 * @param callable(TableIdentifierInterface):Query $generator
	 * @return RestrictionGroup
	 */
	public function whereNotExists($generator) : RestrictionGroup
	{
		$value = $generator($this->table);
		assert($value instanceof Query);
		
		$this->restrictions->push(new Restriction($value, Restriction::NOT_EQUAL_OPERATOR, null));
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
		$this->restrictions->push($group);
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
	
	/**
	 *
	 * @return Collection<Restriction|RestrictionGroup>
	 */
	public function restrictions() : Collection
	{
		return $this->restrictions;
	}
	
	/**
	 *
	 * @param string $name
	 * @param mixed $arguments
	 * @return mixed
	 */
	public function __call($name, $arguments)
	{
		if (method_exists($this->restrictions, $name)) {
			return $this->restrictions->$name(...$arguments);
		}
		
		throw new BadMethodCallException(sprintf('Undefined method RestrictionGroup::%s', $name));
	}
}
