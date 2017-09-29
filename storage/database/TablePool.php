<?php namespace spitfire\storage\database;

use spitfire\cache\MemoryCache;
use spitfire\exceptions\PrivateException;
use Strings;

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
 * Contains a table list for a database.
 * 
 */
class TablePool extends MemoryCache
{
	
	public function contains($key) {
		return 
			parent::contains($key) || 
			parent::contains(Strings::singular($key)) || 
			parent::contains(Strings::plural($key));
	}
	
	public function set($key, $value) {
		if (!$value instanceof Table) { 
			throw new \InvalidArgumentException('Table is required'); 
		}
		
		return parent::set($key, $value);
	}
	
	public function get($key, $fallback = null) {
		
		#Check if the table exists as declared
		try { return parent::get($key)? : parent::set($this->makeTable($key)); } 
		catch (PrivateException$e) {}
		
		#Check if the user used a plural to name the table
		try { return parent::get(Strings::singular($key))? : parent::set($this->makeTable(Strings::singular($key))); } 
		catch (PrivateException$e) {}
		
		#Check if the user used a singular to name the table
		try { return parent::get(Strings::plural($key))? : parent::set($this->makeTable(Strings::plural($key))); } 
		catch (PrivateException$e) {}
		
		#If none of the ways to find a table was satisfactory, we throw an exception
		throw new PrivateException(sprintf('Table %s was not found', $key));
	}
	
	/**
	 * 
	 * @param string $tablename
	 * @return Relation
	 * @throws PrivateException
	 */
	protected function makeTable($tablename) {
		$className = $tablename . 'Model';
		
		if (class_exists($className)) {
			#Create a schema and a model
			$schema = new Schema($tablename);
			$model = new $className();
			$model->definitions($schema);
			
			return new Table($this, $schema);
		}
		
		throw new PrivateException('No table ' . $tablename);
	}
	
}