<?php namespace spitfire\storage\database;

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

class TableLocator
{
	
	
	/**
	 * The database this contains tables for. This is important since the database
	 * offloads table "makes" to the pool.
	 *
	 * @var DB
	 */
	private $db;
	
	/**
	 * Table locators find 
	 * 
	 * @param DB $db
	 */
	public function __construct(DB$db) {
		$this->db = $db;
	}
	
	public function getOTFTable($key) {
		#Get the OTF model
		try {	return $this->set($key, $this->db->getObjectFactory()->getOTFSchema($key)); }
		catch (PrivateException$e) { /*Silent failure again*/}
	}
	
	/**
	 * Makes a new table that the system did not contain. This is done by retrieving
	 * the proper model class, instancing it and requesting it to construct a 
	 * Schema that our database system can work with.
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
			
			return new Table($this->db, $schema);
		}
		
		throw new PrivateException('No table ' . $tablename);
	}
	
}