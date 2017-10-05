<?php namespace spitfire\storage\database;

use InvalidArgumentException;
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
 * Contains a table list for a database. Please note that this system is neither
 * caps sensitive nor is it plural sensitive. When looking for the table
 * "deliveries" it will automatically check for "delivery" too.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class TablePool
{
	
	/**
	 * The database this contains tables for. This is important since the database
	 * offloads table "makes" to the pool.
	 *
	 * @var TableLocator
	 */
	private $tableLocator;
	
	private $cache;
	
	/**
	 * Creates a new Table pool object. This object is designed to cache tables 
	 * across several queries, allowing for them to refer to the same schemas and
	 * data-caches that the tables provide.
	 * 
	 * @param TableLocator $tableLocator
	 */
	public function __construct(TableLocator$tableLocator) {
		$this->tableLocator = $tableLocator;
		$this->cache        = new MemoryCache();
	}
	
	/**
	 * Checks whether the table is already in the pool. This method will automatically
	 * check whether the pool contains a singular / plural of the tablename provided
	 * before returning.
	 * 
	 * @param string $key
	 * @return bool
	 */
	public function contains($key) {
		return 
			$this->cache->contains($key) || 
			$this->cache->contains(Strings::singular($key)) || 
			$this->cache->contains(Strings::plural($key));
	}
	
	/**
	 * Pushes a table into the pool. This method will check that it's receiving a
	 * proper table object.
	 * 
	 * @param string $key
	 * @param Table $value
	 * @return Table
	 * @throws InvalidArgumentException
	 */
	public function set($key, $value) {
		if (!$value instanceof Table) { 
			throw new InvalidArgumentException('Table is required'); 
		}
		
		return $this->cache->set($key, $value);
	}
	
	/**
	 * Returns the Table that the user is requesting from the pool. The pool will
	 * automatically check if the table was misspelled.
	 * 
	 * @param string $key
	 * @param callable $fallback
	 * @return Table
	 * @throws PrivateException
	 */
	public function get($key, $fallback = null) {
		try {
			return $this->search([$key]);
		} catch (Exception $ex) {
			/* Do nothing. We can explore some options here */
		}
		
		try {
			$table = $this->search([Strings::singular($key), Strings::plural($key)]);
			
			trigger_error(
				sprintf('Table %s was misspelled. Prefer %s instead', $key, reset($keys)), 
				E_USER_NOTICE);
			
		} catch (Exception $ex) {
			$table = $this->tableLocator->getOTFTable($key);
		}
		
		#Check if the table was found and return it
		if ($table) { return $table; }
		
		#If none of the ways to find a table was satisfactory, we throw an exception
		throw new PrivateException(sprintf('Table %s was not found', $key));
	}
	
	public function getAll() {
		return $this->cache->getAll();
	}
	
	public function search($keys) {
		
		foreach ($keys as $key) {
			#Check if the table could be found
			$t = $this->cache->get($key, function () { $this->set($this->tableLocator->makeTable($key)); }); 

			#Return the table we found
			if ($t) { return $t; }
		}
	}
	
}