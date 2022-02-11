<?php namespace spitfire\storage\database\grammar\mysql;

use spitfire\storage\database\Aggregate;
use spitfire\storage\database\FieldReference;
use spitfire\storage\database\query\Alias;
use spitfire\storage\database\query\SelectExpression;
use spitfire\storage\database\TableReference;

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
 * A grammar class allows Spitfire to generate the SQL without executing it. This makes
 * it really simple to prepare scripts to be run in batch, outsourced to another app and
 * for unit testing.
 *
 * This grammar allows Spitfire to stringify objects, which is often required in the
 * context of queries.
 */
class MySQLObjectGrammar
{
	
	
	public function tableReference(TableReference $table) : string
	{
		return sprintf('`%s`', $table->getName());
	}
	
	public function fieldReference(FieldReference $field) : string
	{
		return sprintf('%s.`%s`', $this->tableReference($field->getTable()), $field->getName());
	}
	
	public function selectExpression(SelectExpression $s): string
	{
		if ($s->getAggregate()) {
			return sprintf(
				'%s(%s) AS `%s`',
				$s->getAggregate()->getOperation(),
				$this->fieldReference($s->getInput()),
				$s->getAlias()
			);
		}
		elseif ($s->hasAlias()) {
			return sprintf('%s.`%s` AS `%s`', $this->tableReference($s->getInput()->getTable()), $s->getInput()->getName(), $s->getAlias());
		}
		else {
			return sprintf('%s.`%s`', $this->tableReference($s->getInput()->getTable()), $s->getInput()->getName());
		}
	}
	
	public function alias(Alias $alias) : string
	{
		return sprintf('%s AS %s', $this->tableReference($alias->input()), $this->tableReference($alias->output()));
	}
}
