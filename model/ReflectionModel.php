<?php namespace spitfire\model;

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


use ReflectionClass;
use spitfire\model\attribute\Table as TableAttribute;
use spitfire\model\reflection\ReflectsFields;
use spitfire\utils\Strings;

/**
 *
 * @todo Add relationships
 * @todo Add indexes?
 * @todo Cache
 */
class ReflectionModel
{
	
	use ReflectsFields;
	
	/**
	 *
	 * @var class-string
	 */
	private string $classname;
	
	private string $tablename;
	
	/**
	 * Reflects on a model. This allows Spitfire to provide metadata on the
	 * relationships between them.
	 */
	public function __construct(string $classname)
	{
		$this->classname = $classname;
		
		$reflection = new ReflectionClass($this->classname);
		$this->makeTableName($reflection);
		$this->makeFields($reflection);
	}
	
	
	/**
	 * Get the name of the class this reflection is providing metadata for.
	 *
	 * @return string
	 */
	public function getClassname(): string
	{
		return $this->classname;
	}
	
	public function makeTablename(ReflectionClass $reflection) : void
	{
		
		$tableAttribute = $reflection->getAttributes(TableAttribute::class);
		assert(count($tableAttribute) <= 1);
		
		if (!empty($tableAttribute)) {
			$this->tablename = $tableAttribute[0]->newInstance()->getName();
		}
		else {
			$trimmed = Strings::rTrimString($reflection->getShortName(), 'Model');
			$this->tablename = Strings::plural(Strings::snake($trimmed));
		}
	}
	
	
	/**
	 * Get the name of the table the DBMS is managing when reading and or writing
	 * from this model.
	 *
	 * @return string
	 */
	public function getTableName(): string
	{
		return $this->tablename;
	}
}
