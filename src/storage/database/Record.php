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

use spitfire\exceptions\ApplicationException;

class Record
{
	
	/**
	 *
	 * @var (scalar|null)[]
	 */
	private $original;
	
	/**
	 * The raw data that the database contains for this record.
	 *
	 * @var (scalar|null)[]
	 */
	private $data;
	
	/**
	 *
	 * @param (scalar|null)[] $data
	 */
	public function __construct(array $data)
	{
		$this->original = $data;
		$this->data     = $data;
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
		assert($field === null || $this->has($field));
		
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
		foreach ($this->data as $_name => $_value) {
			$_orig = $this->original[$_name]?? null;
			if ($_value !== $_orig) {
				return true;
			}
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
	 * @return scalar|null
	 */
	public function get(string $field)
	{
		return $this->data[$field]?? null;
	}
	
	
	/**
	 * Sets a field to a given value.
	 *
	 * @param string $field
	 * @param scalar|null $value
	 * @return Record
	 */
	public function set(string $field, $value) : Record
	{
		assert($this->has($field));
		
		$this->data[$field] = $value;
		return $this;
	}
	
	public function has(string $name) : bool
	{
		return array_key_exists($name, $this->original);
	}
	
	/**
	 * When slicing a set of keys out of a record, we create a copy of the record
	 * with the selected keys only.
	 *
	 * This will inherit the current record's edited state. So make sure to reset the
	 * record if you need an unaltered version of it.
	 *
	 * @param string[] $keys
	 * @return Record
	 */
	public function slice(array $keys) : Record
	{
		$_record = new Record([]);
		$_original = [];
		$_data = [];
		
		foreach ($keys as $key) {
			assert(array_key_exists($key, $this->original), 'Record is missing key ' . $key);
			assert(array_key_exists($key, $this->data));
			$_original[$key] = $this->original[$key];
			$_data[$key] = $this->data[$key];
		}
		
		$_record->original = $_original;
		$_record->data = $_data;
		
		return $_record;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function keys() : array
	{
		assert(array_keys($this->data) === array_keys($this->original));
		return array_keys($this->data);
	}
	
	/**
	 * Returns the data that the database holds for the given field.
	 *
	 * @param string $field
	 * @return mixed
	 */
	public function original(string $field)
	{
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
		
		foreach (array_keys($this->data) as $_name) {
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
