<?php namespace spitfire\storage\database;


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

class Record
{
	
	/**
	 * The layout/table this record fits into.
	 * 
	 * @var Layout
	 */
	private $layout;
	
	/**
	 * 
	 * @var mixed[]
	 */
	private $original;
	
	/**
	 * The raw data that the database contains for this record.
	 * 
	 * @var mixed[]
	 */
	private $data;
	
	/**
	 * 
	 * @param Layout $layout
	 * @param mixed[] $data
	 */
	public function __construct(Layout $layout, array $data)
	{
		$this->layout   = $layout;
		$this->original = $data;
		$this->data     = $data;
	}
	
	public function getLayout() : Layout
	{
		return $this->layout;
	}
	
	/**
	 * The value of the primary key, null means that the software expects the
	 * database to assign this record a primary key on insert.
	 * 
	 * When editing the primary key value this will ALWAYS return the data that 
	 * the system assumes to be in the database.
	 * 
	 * @var int|string
	 */
	public function getPrimary()
	{
		return $this->original[$this->layout->getPrimaryKey()->getFields()[0]->getName()];
	}
	
	/**
	 * Returns true if the record (or the given data) have not been changed since the
	 * record was created by the database.
	 * 
	 * @param string|null $field
	 * @return bool
	 */
	public function isChanged(string $field = null) : bool
	{
		/**
		 * If the field does not exist, this application is broken. This scenario should
		 * be caught during development, and therefore we consider an assertion sufficient
		 * here.
		 */
		assert($field === null || $this->layout->getField($field));
		
		/**
		 * If the user determined which field has to be checked, then we just check that the
		 * current and original data contain the same data.
		 */
		if ($field !== null) { 
			return $this->data[$field] !== $this->original[$field]; 
		}
		
		/**
		 * Otherwise, loop over the available fields, and check if any of them have different data
		 * in them.
		 */
		foreach ($this->layout->getFields() as $_field) {
			$_name = $_field->getName();
			$_data = $this->data[$_name]?? null;
			$_orig = $this->original[$_name]?? null;
			if ($_data !== $_orig) { return true; }
		}
		
		/**
		 * If no items were found, we can safely say that the data is unchanged since we
		 * read it from the database.
		 */
		return false; 
	}
	
	/**
	 * Returns the current data for the provided field. During development, with assertions,
	 * this method will fail when attempting to read a non-existing field.
	 * 
	 * @param string $field
	 * @return mixed
	 */
	public function get(string $field)
	{
		assert(!!$this->layout->getField($field));
		return $this->data[$field]?? null;
	}
	
	
	/**
	 * Sets a field to a given value.
	 * 
	 * @param string $field
	 * @param mixed $value
	 * @return Record
	 */
	public function set(string $field, $value) : Record
	{
		/**
		 * Only when assertions are enabled we perform a check whether this field is actually
		 * intended to be written at all.
		 */
		assert(!!$this->layout->getField($field));
		
		$this->data[$field] = $value;
		return $this;
	}
	
	/**
	 * Returns the data that the database holds for the given field.
	 * 
	 * @param string $field
	 * @return mixed
	 */
	public function original(string $field) : mixed
	{
		assert(!!$this->layout->getField($field));
		return $this->original[$field]?? null;
	}
	
	/**
	 * Returns the current data. This means that the system expects this data to be
	 * in the DBMS when the data is saved.
	 * 
	 * @return mixed[]
	 */
	public function raw() : array
	{
		return $this->data;
	}
	
	/**
	 * Returns the data that the system expects to be contained in the database right now.
	 * 
	 * @return mixed[]
	 */
	public function rawOriginal() : array
	{
		return $this->original;
	}
	
	/**
	 * Generate a diff of data that needs to be written to the database.
	 * 
	 * @return mixed[]
	 */
	public function diff() : array
	{
		$_diff = [];
		
		foreach ($this->layout->getFields() as $_field) {
			$_name = $_field->getName();
			if (($this->data[$_name]?? null) !== ($this->original[$_name]?? null)) {
				$_diff[$_name] = $this->data[$_name];
			}
		}
		
		return $_diff;
	}
	
	/**
	 * Commit all the changes to the record. Please note that this operation should not be
	 * invoked manually, since it's not implied that the database will be written to.
	 */
	public function commit() : void
	{
		$this->original = $this->data;
	}
	
	/**
	 * Discard the changes to the record, restore all the original values into data.
	 */
	public function rollback() : void
	{
		$this->data = $this->original;
	}
	
}
