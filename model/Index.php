<?php namespace spitfire\model;

use spitfire\collection\Collection;

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
 * 
 * @template T of Model
 */
class Index
{
	
	/**
	 * The fields that the index contains. Please note that many DBMS (basically 
	 * all of them) are sensitive to the order in which the fields are provided
	 * to the index and they will therefore perform better (or worse) when used 
	 * properly.
	 *
	 * @var Collection<Field<T>>
	 */
	private $fields;
	
	/**
	 * The name to be given to this index. This will be then suggested to the 
	 * DBMS. This does not guarantee to be the name the system will end up choosing.
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * Indicates whether this index is unique. Please note that spitfire will 
	 * override this setting to bool(true) if the index is also primary.
	 *
	 * @var bool 
	 */
	private $unique = false;
	
	/**
	 * Indicates whether this key is a primary key. Every table can only have one 
	 * primary key and it is required to have one to create relations between the
	 * tables.
	 *
	 * @var bool
	 */
	private $primary = false;
	
	/**
	 * Creates a new index for the schema.
	 * 
	 * @param Collection<Field<T>> $fields
	 */
	public function __construct(Collection $fields = null)
	{
		$this->fields = new Collection($fields);
	}
	
	/**
	 * Return the field collection
	 * 
	 * @return Collection<Field<T>> containing the <code>Field</code>s in this index
	 */
	public function getFields() : Collection
	{
		return $this->fields;
	}
	
	/**
	 * Indicates whether a field is contained in this index. This allows an app
	 * to check whether it needs to remove an index when a field is removed.
	 * 
	 * @param \spitfire\model\Field<T> $f
	 * @return bool
	 */
	public function contains(Field $f) : bool
	{
		return $this->fields->contains($f);
	}
	
	/**
	 * Returns the name of the index (if given) and generates a standard name for
	 * the index when there is none. The format for these is
	 * 
	 * idx_tablename_field1_field2
	 * 
	 * @return string
	 */
	public function getName()
	{
		/*
		 * If the index already has a name we roll with that.
		 */
		if (!empty($this->name)) {
			return $this->name; 
		}
		
		/*
		 * Get the table name, this way we can generate a meaningful index name
		 * when it's written to the database.
		 */
		$tablename  = $this->fields->first()->getModel()->getTableName();
		
		/*
		 * Implode the names of the fields being passed to the index. This way the 
		 * 
		 */
		$imploded = $this->fields->each(function ($e) { 
			return $e->getName();
		})->join('_');
		
		/*
		 * Generate a name from the fields for the index
		 * - All indexes are identified by idx
		 * - Then comes the table name
		 * - Lastly we add the fields composing the index
		 */
		return $this->name = 'idx_' . $tablename . '_' . $imploded;
	}
	
	/**
	 * Returns whether the field can contain repeated data. Please note that this
	 * can be due to the field being unique or primary.
	 * 
	 * @return bool
	 */
	public function isUnique() : bool
	{
		return $this->unique || $this->primary;
	}
	
	/**
	 * Is this field a primary key? This method will tell you the truth.
	 * 
	 * @return bool
	 */
	public function isPrimary() : bool
	{
		return $this->primary;
	}
	
	/**
	 * Set the fields that this index spans.
	 * 
	 * @param Collection<Field<T>> $fields
	 * @return self<T>
	 */
	public function setFields(Collection $fields) : self
	{
		$this->fields = $fields;
		return $this;
	}
	
	/**
	 * Adds a field to the collection of fields.
	 * 
	 * @param Field<T> $field
	 * @return self<T>
	 */
	public function putField(Field $field) : self
	{
		$this->fields->push($field);
		return $this;
	}
	
	/**
	 * Sets the name of the index. The name must be a valid identifier for the 
	 * DBMS. Please note that Spitfire will not validate this.
	 * 
	 * @param string $name
	 * @return self<T>
	 */
	public function setName(string $name) : self
	{
		$this->name = $name;
		return $this;
	}
	
	/**
	 * Set the index to be unique.
	 * 
	 * @param bool $unique
	 * @return self<T>
	 */
	public function unique(bool $unique = true) : self
	{
		$this->unique = $unique;
		return $this;
	}
	
	
	/**
	 * Set the index to be the table's primary key. Please note that Spitfire
	 * will not check if this is a duplicate.
	 * 
	 * @param bool $isPrimary
	 * @return self<T>
	 */
	public function setPrimary(bool $isPrimary) : self
	{
		$this->primary = $isPrimary;
		return $this;
	}
}
