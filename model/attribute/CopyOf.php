<?php namespace spitfire\model\attribute;
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


use spitfire\model\ReflectionModel;
use spitfire\storage\database\drivers\SchemaMigrationExecutorInterface;
use spitfire\storage\database\drivers\TableMigrationExecutorInterface;

class CopyOf extends Type
{
	
	private string $model;
	private string $field;
	
	/**
	 * 
	 */
	public function __construct(string $model, string $field)
	{
		$this->model = $model;
		$this->field = $field;
	}
	
	public function migrate(SchemaMigrationExecutorInterface $schema, TableMigrationExecutorInterface $migrator, string $name, bool $nullable): void
	{
		$model = new ReflectionModel($this->model);
		$ref = $schema->table($model->getTableName());
		$layout = $ref->layout();
		
		/**
		 * @todo We currently enforce all primary keys to be long integers for this to work.
		 * Technically they could be whatever they wanted.
		 */
		assert($layout->getField($this->field)->getType() === 'long:unsigned', sprintf('Field %s should be long:unsigned', $this->field));
		
		/**
		 * Push the field onto our model. We concatenate the local with the remote field in the
		 * DBMS, so we get something like employee_id when referencing another taable.
		 */
		$migrator->long($name, true, true);
		
		/**
		 * There's explicitly no call to $migrator->foreign(). This would generate two references when
		 * creating the tables. This is not expected behavior
		 */
	}
}
