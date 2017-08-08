<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use spitfire\core\Environment;
use spitfire\exceptions\PrivateException;
use spitfire\storage\database\Field;
use spitfire\storage\database\LayoutInterface;
use spitfire\storage\database\Table;

/* 
 * The MIT License
 *
 * Copyright 2017 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class Layout implements LayoutInterface
{
	/**
	 * The table that the system uses to connect layout and relation.
	 *
	 * @var Table
	 */
	private $table;
	
	/**
	 * The prefixed name of the table. The prefix is defined by the environment
	 * and allows to have several environments on the same database.
	 *
	 * @var string
	 */
	private $tablename;
	
	/**
	 * List of the physical fields this table handles. This array is just a 
	 * shortcut to avoid looping through model-fields everytime a query is
	 * performed.
	 *
	 * @var Field[] 
	 */
	private $fields;
	
	/**
	 * An array of indexes that this table defines to manage it's queries and 
	 * data.
	 *
	 * @var \spitfire\storage\database\IndexInterface[]
	 */
	private $indexes;
	
	/**
	 * 
	 * @param Table $table
	 */
	public function __construct(Table$table) {
		#Assume the table
		$this->table = $table;
		
		#Get the physical table name. This will use the prefix to allow multiple instances of the DB
		$this->tablename = Environment::get('db_table_prefix') . $table->getSchema()->getTableName();
		
		#Create the physical fields
		$fields  = $this->table->getSchema()->getFields();
		$columns = Array();
		
		foreach ($fields as $field) {
			$physical = $field->getPhysical();
			while ($phys = array_shift($physical)) { $columns[$phys->getName()] = $phys; }
		}
		
		$this->fields = $columns;
	}
	
	public function create() {
		
	}

	public function destroy() {
		
	}
	
	/**
	 * Fetch the fields of the table the database works with. If the programmer
	 * has defined a custom set of fields to work with, this function will
	 * return the overriden fields.
	 * 
	 * @return Field[] The fields this table handles.
	 */
	public function getFields() {
		return $this->fields;
	}
	
	public function getField($name) : Field {
		#If the data we get is already a DBField check it belongs to this table
		if ($name instanceof Field) {
			if ($name->getTable() === $this->table) { return $name; }
			else { throw new PrivateException('Field ' . $name . ' does not belong to ' . $this); }
		}
		
		#Otherwise search for it in the fields list
		if (isset($this->fields[(string)$name])) { return $this->fields[(string)$name]; }
		
		#The field could not be found in the Database
		throw new PrivateException('Field ' . $name . ' does not exist in ' . $this);
	}
	
	public function getIndexes() {
		//TODO Implement
	}

	public function getTableName() : string {
		
	}

	public function repair() {
		
	}
	
	/**
	 * Returns the name of a table as DB Object reference (with quotes).
	 * 
	 * @return string The name of the table escaped and ready for use inside
	 *                of a query.
	 */
	public function __toString() {
		return "`{$this->tablename}`";
	}

}