<?php namespace spitfire\storage\database\identifiers;

use spitfire\collection\Collection;
use spitfire\storage\database\identifiers\FieldIdentifier;
use spitfire\storage\database\identifiers\IdentifierInterface;
use spitfire\storage\database\identifiers\TableIdentifierInterface;

/**
 * The query table wraps a table and provides a consistent aliasing mechanism.
 * This allows the system to reference tables within the database system across
 * queries.
 *
 * For example, when performing a query that requires a table to be joined twice,
 * the application needs to consistently alias the fields in the query. In SQL
 * we usually write something like
 *
 * SELECT * FROM orders LEFT JOIN customers c1 ON (...) LEFT JOIN customers c2 ON (...)
 *
 * And then reference the fields within them as c1.id or c2.id. Otherwise, the DBMS
 * will fail, indicating that the field `id` is ambiguous.
 */
class TableIdentifier implements TableIdentifierInterface
{
	
	/**
	 * This table provides all the information (metadata and fields) about the table
	 * being queried.
	 *
	 * @var string[]
	 */
	private $raw;
	
	/**
	 *
	 * @var Collection<string>
	 */
	private $fields;
	
	/**
	 * The following variables manage the aliasing system inside spitfire. To avoid
	 * having different tables with the same name in them, Spitfire uses aliases
	 * for the tables. These aliases are automatically generated by adding a unique
	 * number to the table's name.
	 *
	 * The counter is in charge of making sure that every table is uniquely named,
	 * every time a new query table is created the current value is assigned and
	 * incremented.
	 *
	 * @var int
	 */
	private static $counter = 1;
	
	/**
	 *
	 * @param string[] $table
	 * @param Collection<string> $fields
	 */
	public function __construct(array $table, Collection $fields)
	{
		assert(count($table) > 0 && count($table) < 3);
		
		#In case this table is aliased, the unique alias will be generated using this.
		$this->raw = $table;
		$this->fields = $fields;
	}
	
	/**
	 * Creates a copy of this query table, generating a new ID in the process. This is
	 * due to the fact that these aliases are intended to be immutable.
	 *
	 * @return TableIdentifierInterface
	 */
	public function withAlias() : TableIdentifierInterface
	{
		$id = self::$counter++;
		
		$raw = $this->raw;
		$last = sprintf('%s_%s', array_pop($raw), $id);
		array_push($raw, $last);
		
		return new TableIdentifier($raw, $this->fields);
	}
	
	/**
	 * Retrieves the table's alias. Please note that if the table is set to not alias,
	 * the system will return the table name. This quirk makes the method rather convenient
	 * to use.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		$raw = $this->raw;
		$last = array_pop($raw);
		
		/**
		 * If a table identifier happens to be empty, the application has a severe malfunction
		 * somewhere.
		 */
		assert($last !== null);
		return $last;
	}
	
	/**
	 *
	 * @return Collection<IdentifierInterface>
	 */
	public function getOutputs(): Collection
	{
		/**
		 * @var Collection<IdentifierInterface>
		 */
		$t = $this->fields->each(function (string $field) : IdentifierInterface {
			return new FieldIdentifier(array_merge($this->raw, [$field]));
		});
		
		return $t;
	}
	
	public function getOutput(string $name): FieldIdentifier
	{
		assert($this->fields->contains($name));
		return new FieldIdentifier(array_merge($this->raw, [$name]));
	}
	
	public function raw(): array
	{
		return $this->raw;
	}
}
