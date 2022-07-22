<?php namespace spitfire\storage\database\identifiers;

/**
 * The query field object is a component that allows a Query to wrap a field and
 * connect it to itself. This is important for the DBA since it allows the app
 * to establish connections between the different queries when assembling SQL
 * or similar.
 *
 * When a query is connected to a field, you may use this to establish relationships
 * and create complex queries that can properly be joined.
 *
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class FieldIdentifier implements IdentifierInterface
{
	/**
	 * The actual database field.
	 *
	 * @var string[]
	 */
	private $raw;
	
	/**
	 * Instances a new Queryfield. This object scopes a field to a certain query,
	 * which makes it very versatile for referencing fields accross queries in
	 * join operations.
	 *
	 * @param string[] $raw
	 */
	public function __construct(array $raw)
	{
		$this->raw = $raw;
	}
	
	/**
	 * Returns the name of the field that underlies the queryfield.
	 *
	 * @return string[]
	 */
	public function raw(): array
	{
		return $this->raw;
	}
}
