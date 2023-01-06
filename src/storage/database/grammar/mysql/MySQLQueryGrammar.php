<?php namespace spitfire\storage\database\grammar\mysql;

use spitfire\collection\Collection;
use spitfire\exceptions\ApplicationException;
use spitfire\storage\database\grammar\QueryGrammarInterface;
use spitfire\storage\database\identifiers\IdentifierInterface;
use spitfire\storage\database\OrderBy;
use spitfire\storage\database\Query;
use spitfire\storage\database\query\Join;
use spitfire\storage\database\query\JoinQuery;
use spitfire\storage\database\query\JoinTable;
use spitfire\storage\database\QuoterInterface;
use spitfire\storage\database\query\Restriction;
use spitfire\storage\database\query\RestrictionGroup;
use spitfire\storage\database\query\SelectExpression;

/*
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301  USA
 */

/**
 * This class aggregates the logic to create record related SQL statements,
 * allowing the application to abstract it's behavior a little further.
 */
class MySQLQueryGrammar implements QueryGrammarInterface
{
	
	/**
	 *
	 * @var QuoterInterface
	 */
	private $quoter;
	
	/**
	 *
	 * @var MySQLObjectGrammar
	 */
	private $object;
	
	public function __construct(QuoterInterface $quoter)
	{
		$this->quoter = $quoter;
		$this->object = new MySQLObjectGrammar($this);
	}
	
	/**
	 * Stringify a SQL query for MySQL.
	 *
	 * @param Query $query
	 * @return string
	 */
	public function query(Query $query) : string
	{
		$sentence = new Collection([
			'SELECT',
			$this->selectExpression($query),
			'FROM',
			$this->object->alias($query->getFrom()),
			$this->tableReferences($query),
			'WHERE',
			$this->whereConditions($query->getRestrictions()),
			$this->groupBy($query),
			$this->orderBy($query),
			$this->limit($query)
		]);
		
		return $sentence->filter()->join(' ');
	}
	
	/**
	 * Generate the select expression for the query.
	 *
	 * @param Query $query
	 * @return string
	 */
	public function selectExpression(Query $query) : string
	{
		$select = $query->getOutputs();
		$_return = [];
		
		assert($select->containsOnly(SelectExpression::class));
		
		foreach ($select as $output) {
			$_return[] = $this->object->selectExpression($output);
		}
		
		return implode(', ', $_return);
	}
	
	public function tableReferences(Query $query) : string
	{
		$joins = $query->getJoined();
		$_return = [];
		
		foreach ($joins as $join) {
			$_return[] = $this->joined($join);
		}
		
		return implode(' ', $_return);
	}
	
	public function joined(Join $join) : string
	{
		if ($join instanceof JoinTable) {
			return $this->joinedTable($join);
		}
		if ($join instanceof JoinQuery) {
			return $this->joinedQuery($join);
		}
		throw new ApplicationException('Impossible condition ' . get_class($join));
	}
	
	public function joinedTable(JoinTable $join) : string
	{
		return sprintf('LEFT JOIN %s ON (%s)', $this->object->alias($join->getAlias()), $this->whereConditions($join->getRestrictions()));
	}
	
	public function joinedQuery(JoinQuery $join) : string
	{
		return sprintf(
			'LEFT JOIN (%s) AS %s ON (%s)',
			$this->query($join->getSubQuery()->getQuery()),
			$this->object->identifier($join->getSubQuery()->getTable()),
			$this->whereConditions($join->getRestrictions())
		);
	}
	
	public function whereConditions(RestrictionGroup $restrictions) : string
	{
		/**
		 * A 1 is the way of MySQL to basically not filter anything.
		 */
		if ($restrictions->restrictions()->isEmpty()) {
			return '1';
		}
		
		return $restrictions->restrictions()->each(function ($r) {
			if ($r instanceof RestrictionGroup) {
				return $this->whereConditions($r);
			}
			if ($r instanceof Restriction) {
				return $this->restriction($r);
			}
		})
			->join($restrictions->getType() === RestrictionGroup::TYPE_AND? ' AND ' : ' OR ');
		;
	}
	
	public function restriction(Restriction $restriction) : string
	{
		$field = $restriction->getField();
		$operator = $restriction->getOperator();
		$value = $restriction->getValue();
		
		if ($field instanceof Query) {
			assert($field->getOutputs()->count() === 1);
			
			if ($value === null) {
				return sprintf(
					'%s (%s)',
					$restriction->getOperator() == Restriction::EQUAL_OPERATOR? 'EXISTS' : 'NOT EXISTS',
					$this->query($field)
				);
			}
			else {
				#TODO: Implement
			}
		}
		
		if ($value === null) {
			return $this->object->identifier(
				$field
			) . ($operator === '='? ' IS NULL' : ' IS NOT NULL'
			);
		}
		
		if ($value instanceof Query) {
			assert($value->getOutputs()->count() === 1);
			
			return sprintf(
				'%s %s %s',
				$this->object->identifier($field),
				$restriction->getOperator(),
				$this->query($value)
			);
		}
		
		if ($value instanceof IdentifierInterface) {
			return sprintf(
				'%s %s %s',
				$this->object->identifier($field),
				$restriction->getOperator(),
				$this->object->identifier($value)
			);
		}
		
		return sprintf(
			'%s %s %s',
			$this->object->identifier($field),
			$restriction->getOperator(),
			$this->quoter->quote(strval($value))
		);
	}
	
	/**
	 * The gr
	 *
	 * @param Query $query
	 * @return string
	 */
	public function groupBy(Query $query) : string
	{
		
		$grouped = (new Collection($query->getGroupBy()))->each(function (IdentifierInterface $e) {
			return $this->object->identifier($e);
		});
		
		return $grouped->isEmpty()? '' : 'GROUP BY ' . $grouped->join(', ');
	}
	
	/**
	 * Generates an order statement for your query.
	 *
	 * @param Query $query
	 * @return string
	 */
	public function orderBy(Query $query) : string
	{
		$columns = $query->getOrder()->each(function (OrderBy $order) {
			$output = $order->getOutput();
			
			if ($output instanceof IdentifierInterface) {
				return $this->object->identifier($output) . ' ' . $order->getDirection();
			}
			
			/**
			 * If the output is the result of an anonymous aggregation, we need to treat it
			 * with special care.
			 */
			return sprintf('`%s`', $output);
		});
		
		return $columns->isEmpty()? '' : 'ORDER BY ' . $columns->join(', ');
	}
	
	/**
	 * Returns the limit expression for the query so the data complies with the
	 * restrictions the application needs.
	 *
	 * Limiting the resultset is commonly used for pagination, although, for
	 * performance it is recommended to avoid the offset.
	 *
	 * @param Query $query
	 * @return string
	 */
	public function limit(Query $query) : string
	{
		$offset = $query->getOffset();
		$limit  = $query->getLimit();
		
		if ($limit !== null && $offset !== null) {
			return sprintf('LIMIT %s, %s', $offset, $limit);
		}
		
		elseif ($limit !== null) {
			return sprintf('LIMIT %s', $limit);
		}
		
		return '';
	}
}
