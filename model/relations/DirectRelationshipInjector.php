<?php namespace spitfire\model\relations;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-13 01  USA
 *
 */


/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-13 01  USA
 *
 */


use spitfire\model\Field;
use spitfire\model\Model;
use spitfire\model\query\ExtendedRestrictionGroupBuilder;
use spitfire\model\query\RestrictionGroupBuilderInterface;
use spitfire\model\QueryBuilder;
use spitfire\model\QueryBuilderBuilder;
use spitfire\storage\database\events\QueryBeforeCreateEvent;
use spitfire\storage\database\identifiers\TableIdentifierInterface;
use spitfire\storage\database\Query as DatabaseQuery;
use spitfire\storage\database\query\RestrictionGroup;

/**
 * Direct relationships are usually the BelongsToOne or HasMany kind of. Querying for
 * existence or absence, simply means looking up whether the remote (referenced) table
 * contains a record that matches what we established.
 *
 * @template LOCAL of Model
 * @template REMOTE of Model
 * @implements RelationshipInjectorInterface<REMOTE>
 */
class DirectRelationshipInjector implements RelationshipInjectorInterface
{
	
	/**
	 *
	 * @var Field<LOCAL>
	 */
	private Field $field;
	
	/**
	 *
	 * @var Field<REMOTE>
	 */
	private Field $referenced;
	
	
	/**
	 *
	 * @param Field<LOCAL> $field
	 * @param Field<REMOTE> $referenced
	 */
	public function __construct(Field $field, Field $referenced)
	{
		$this->field = $field;
		$this->referenced = $referenced;
	}
	
	/**
	 *
	 * @param ExtendedRestrictionGroupBuilder<REMOTE> $restrictions
	 * @param callable(QueryBuilderBuilder<REMOTE>):QueryBuilder<REMOTE> $payload
	 */
	public function existence(ExtendedRestrictionGroupBuilder $restrictions, callable $payload): void
	{
		
		$connection = $restrictions->connection();
		
		/**
		 * Create a subquery that will link the table we're querying with the table we're referencing.
		 * The user provided closure can write restrictions into that query.
		 */
		$restrictions->getDBRestrictions()
			->whereExists(function (TableIdentifierInterface $table) use ($connection, $payload): DatabaseQuery {
				$model = $this->referenced->getModel();
				$builder = $payload((new QueryBuilderBuilder($connection, $model))->withoutSelects());
				
				/**
				 * Create the restriction that filters the second table by connecting it to the first one.
				 * This is the part that generates the ON table.ref_id = referenced._id
				 */
				$builder->getRestrictions()->where(
					$this->referenced->getName(),
					$table->getOutput($this->field->getName())
				);
				
				$query = $builder->getQuery();
				
				/**
				 * The query may not select anything from the result. If it did,
				 * the code is broken and the behavior is unpredictable.
				 */
				assert($query->getOutputs()->count() === 0);
				
				$query->selectField($query->getFrom()->output()->getOutput($this->referenced->getName()));
				
				return $query;
			});
	}
	
	/**
	 *
	 * @param ExtendedRestrictionGroupBuilder<REMOTE> $restrictions
	 * @param callable(QueryBuilderBuilder<REMOTE>):QueryBuilder<REMOTE> $payload
	 */
	public function absence(ExtendedRestrictionGroupBuilder $restrictions, callable $payload): void
	{
		
		$connection = $restrictions->connection();
		
		/**
		 * Create a subquery that will link the table we're querying with the table we're referencing.
		 * The user provided closure can write restrictions into that query.
		 */
		$restrictions->getDBRestrictions()
			->whereNotExists(function (TableIdentifierInterface $table) use ($connection, $payload): DatabaseQuery {
				$model = $this->referenced->getModel();
				$builder = $payload((new QueryBuilderBuilder($connection, $model))->withoutSelects());
				
				/**
				 * Create the restriction that filters the second table by connecting it to the first one.
				 * This is the part that generates the ON table.ref_id = referenced._id
				 */
				$builder->getRestrictions()->where(
					$this->referenced->getName(),
					$table->getOutput($this->field->getName())
				);
				
				$query = $builder->getQuery();
				$query->selectField($query->getFrom()->output()->getOutput($this->referenced->getName()));
				
				/**
				 * The query may not select more than one field from the result. If it did,
				 * the code is broken and the behavior is unpredictable.
				 */
				assert($query->getOutputs()->count() === 1);
				
				return $query;
			});
	}
}
