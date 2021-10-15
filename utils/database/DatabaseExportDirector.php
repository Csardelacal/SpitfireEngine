<?php namespace spitfire\utils\database;

use spitfire\cli\arguments\CLIParameters;
use spitfire\mvc\Director;

/* 
 * The MIT License
 *
 * Copyright 2020 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * The database director allows to use the models to quickly create the database
 * schema on the DBMS, and import and export data from the DBMS to a File system.
 * 
 * This util should also make it infinitely more easy to change data to a new encoding.
 * Sadly, MySQL, MariaDB (and I suspect the other DBMSs too) are a real pain to 
 * change the encoding and collation. Just being able to export the tables, load them
 * into files and write them back into the database seems like a godsend for many
 * of these operations.
 * 
 * It also makes it more approachable to backup and restore data, since many of the
 * SQL based import/export tools will cause artifacts.
 * 
 * @todo Move to Database rather than utils
 * @todo Allow for incremental updates (this is tricky due to the fact that deletes may happen)
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class DatabaseExportDirector extends Director
{
	
	/**
	 * This currently accepts no parameters at all
	 */
	public function parameters(): array
	{
		return [
			
		];
	}
	
	/**
	 * Initializes the schemas on the DBMS side. This removes the need for spitfire's
	 * old mechanism where it would fall back from these errors and attempt to fix
	 * the database for each request.
	 * 
	 * @todo Introduce migrations / repairs / upgrades to add and remove indexes
	 * @todo This only creates tables for the top level application, child apps are not processed
	 * @todo This has debugging output that needs to go
	 * @todo Replace this with dependency injected database?
	 * @todo Spitfire has no abstracted mean to load / list models, controllers, directors, 
	 *		templates that removes the need for this glob madness and allows it to cache
	 *		known locations
	 */
	public function exec(array $parameters, CLIParameters $arguments): int
	{
		
		$targetdir = $parameters[0];
		$db = db();
		$dir = basedir() . '/bin/models/';
		
		$walk = function ($dir, $namespace) use (&$walk, $db) {
			/*
			 * 
			 */
			$scripts = glob($dir . '*.php');
			$_ret    = [];
			
			foreach ($scripts as $file) {
				console()->info($file)->ln();
				$table = $db->table($namespace . '\\' . explode('.', basename($file))[0]);
				console()->success($table->getLayout()->getTableName())->ln();
				$_ret[] = $table;
			}
			
			$folders = glob($dir . '*', GLOB_ONLYDIR);
			
			foreach ($folders as $folder) {
				$_ret = array_merge($_ret, $walk($dir . basename($folder) . '/', $namespace . '\\' . basename($folder)));
			}
			
			return $_ret;
		};
		
		$tables = $walk($dir, '');
		
		foreach ($tables as $table) {
			$records = $table->getAll()->all();
			$output  = fopen($targetdir . '/' . trim($table->getSchema()->getName(), '\/') . '.backup', 'w+');
			
			foreach ($records as /*@var $record \spitfire\Model*/$record) {
				$data = $record->getData();
				$raw = [];
				
				foreach ($data as $name => $field) {
					$_data = $field->dbGetData();
					$raw[$name] = $_data;
				}
				
				fwrite($output, json_encode($raw));
				fwrite($output, PHP_EOL);
			}
		}
		
		return 0;
	}
	
}
