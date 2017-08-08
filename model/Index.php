<?php namespace spitfire\model;

use spitfire\core\Collection;

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

/**
 * An Index allows your application to define a series of fields that the DBMS 
 * should index in order to improve performance of data retrieval.
 * 
 * Please note that indexes on the logical level are "suggestions" that allow
 * the DBMS to improve performance, but these are not required to be followed.
 */
class Index
{
	
	private $fields;
	private $name;
	private $unique = false;
	private $isPrimary = false;
	
	/**
	 * Creates a new index for the schema.
	 * 
	 * @param Field[] $fields
	 */
	public function __construct($fields = null) {
		$this->fields = new Collection($fields);
		$this->name = 'idx_' . $this->fields->rewind()->getSchema()->getName() . rand(0, 9999);
	}
	
	public function getFields() {
		return $this->fields;
	}
	
	public function contains(Field$f) {
		return $this->fields->contains($f);
	}
	
	public function getName() {
		return $this->name;
	}
	
	public function getUnique() {
		return $this->unique;
	}
	
	public function isPrimary() {
		return $this->isPrimary;
	}
	
	public function setFields($fields) {
		$this->fields = $fields;
		return $this;
	}
	
	public function putField(Field$field) {
		$this->fields->push($field);
	}
	
	public function setName($name) {
		$this->name = $name;
		return $this;
	}
	
	public function setUnique($unique) {
		$this->unique = $unique;
		return $this;
	}
	
	public function setPrimary($isPrimary) {
		$this->isPrimary = $isPrimary;
		return $this;
	}
}