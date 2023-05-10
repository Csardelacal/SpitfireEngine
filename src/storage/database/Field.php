<?php namespace spitfire\storage\database;

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


/**
 * The 'database field' class is an adapter used to connect logical fields
 * (advanced fields that can contain complex data) to simplified versions
 * that common DBMSs can use to store this data.
 *
 * This class should be extended by each driver to allow it to use them in an
 * efficient manner for them.
 *
 * @todo Adding a mechanism to establish defaults would be nice.
 */
class Field
{
	
	/**
	 * Provides a name that the DBMS should use to name this field. Usually
	 * this will be exactly the same as for the logical field, except for
	 * fields that reference others.
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The type is the data that this field contains. This is usually referenced
	 * as a string containing a data type and optionally a colon with metadata
	 * like
	 *
	 * * int
	 * * string:4
	 * * enum:hello,world
	 *
	 * @todo Maybe introduce a type class that allows pushing this data
	 * @var string
	 */
	private $type;
	
	/**
	 * Most databases allow certain datatypes to be automatically incremented if
	 * the data is set to an empty value.
	 *
	 * Please note that most database engines will require the field to be part
	 * of the primary key to be automatically incremented. Also, most DBMS will
	 * not allow your application to define more than one auto incrementing field
	 * per table.
	 *
	 * @var bool
	 */
	private $autoIncrements = false;
	
	/**
	 * Indicates whether the field can receive null values. This is important for runtime
	 * validation, so the system can ensure that data written to the database is not bad.
	 *
	 * @var bool
	 */
	private $nullable;
	
	/**
	 * Creates a new Database field. This fields provide information about
	 * how the DBMS should hadle one of Spitfire's Model Fields. The Model
	 * Fields, also referred to as Logical ones can contain data that
	 * requires several DBFields to store, this class creates an adapter
	 * to easily handle the different objects.
	 *
	 * @param string $name
	 * @param string $type
	 * @param bool $nullable
	 * @param bool $autoIncrement
	 */
	public function __construct(string $name, string $type, bool $nullable, bool $autoIncrement = false)
	{
		$this->type = $type;
		$this->nullable = $nullable;
		$this->name = $name;
		$this->autoIncrements = $autoIncrement;
	}
	
	/**
	 * Returns the fully qualified name for this column on the DBMS. Fields
	 * referring to others will return an already prefixed version of them
	 * like 'field_remote'.
	 *
	 * In order to obtain the field name you can request it from the logical
	 * field. And to obtain the remote name you can request it from the
	 * referenced field.
	 *
	 * @return string
	 */
	public function getName() : string
	{
		return $this->name;
	}
	
	/**
	 * Set the name of the field. This defines how the DBMS should be addressed
	 * to locate the field. Please note that not all DBMS's support all names, it's
	 * therefore recommended to use simple ASCII names whenever possible.
	 *
	 * @param string $name
	 * @return Field
	 */
	public function setName(string $name) : Field
	{
		$this->name = $name;
		return $this;
	}
	
	public function isAutoIncrement() : bool
	{
		return $this->autoIncrements;
	}
	
	/**
	 * Make this field auto-increment. This means that if written with a null value to the
	 * DBMS, the DBMS will assign an automatically increasing value.
	 *
	 * @param bool $autoIncrement
	 * @return Field
	 */
	public function setAutoIncrement(bool $autoIncrement) : Field
	{
		$this->autoIncrements = $autoIncrement;
		return $this;
	}
	
	public function isNullable() : bool
	{
		return $this->nullable;
	}
	
	/**
	 * Set the field to be nullable, or not.
	 *
	 * @param bool $nullable
	 * @return Field
	 */
	public function setNullable(bool $nullable) : Field
	{
		$this->nullable = $nullable;
		return $this;
	}
	
	/**
	 * Returns true if this field accepts null values when writing to the database.
	 *
	 * @return bool
	 */
	public function getNullable() : bool
	{
		return $this->nullable;
	}
	
	
	/**
	 * Set the field's type.
	 *
	 * @param string $type
	 * @return Field
	 */
	public function setType(string $type) : Field
	{
		$this->type = $type;
		return $this;
	}
	
	/**
	 * Returns the type of data that this field accepts, this is usually either a simple
	 * type or a colon separated value that contains some qualifier.
	 *
	 * * int
	 * * string:4
	 * * enum:hello,world
	 *
	 * @return string
	 */
	public function getType() : string
	{
		return $this->type;
	}
}
