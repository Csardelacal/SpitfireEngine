<?php namespace spitfire\storage\database\query;

use spitfire\storage\database\identifiers\FieldIdentifierInterface;
use spitfire\storage\database\identifiers\IdentifierInterface;
use spitfire\storage\database\identifiers\TableIdentifier;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
use spitfire\storage\database\Query;

class QueryOrTableIdentifier
{
	
	public function __construct(
		private Query|TableIdentifierInterface $query
	) {
	}
	
	public function isQuery() : bool
	{
		return $this->query instanceof Query;
	}
	
	public function getQuery() : Query
	{
		assert($this->query instanceof Query);
		return $this->query;
	}
	
	public function getTableIdentifier() : TableIdentifierInterface
	{
		assert($this->query instanceof TableIdentifierInterface);
		return $this->query;
	}
	
	public function withAlias() : TableIdentifierInterface
	{
		if ($this->query instanceof Query) {
			return new TableIdentifier(
				['t_' . rand()],
				$this->query->getOutputs()->each(fn(SelectExpression $e) => $e->getName())
			);
		}
		else {
			return $this->query->withAlias();
		}
	}
}
