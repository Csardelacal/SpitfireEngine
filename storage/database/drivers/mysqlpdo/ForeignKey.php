<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use spitfire\core\Collection;
use spitfire\storage\database\Field;
use spitfire\storage\database\ForeignKeyInterface;

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

class ForeignKey extends Index implements ForeignKeyInterface
{
	
	public function getReferenced(): Collection {
		$fields  = $this->getFields();
		$_ret    = new Collection();
		
		$fields->each(function(Field$e) use ($_ret) {
			$_ret->push($e->getReferencedField());
		});
		
		return $_ret;
	}
	
	public function getName(): string {
		return 'foreign_' . parent::getName();
	}
	
	public function definition() {
		$referenced = $this->getReferenced();
		$table      = $referenced->rewind()->getTable();
		
		return sprintf(
			'FOREIGN KEY `%s` (%s) REFERENCES %s(%s) ON DELETE CASCADE ON UPDATE CASCADE',
			$this->getName(),
			$this->getFields()->each(function ($e) { return sprintf('`%s`', $e->getName()); })->join(', '),
			$table->getLayout(),
			$referenced->each(function ($e) { return sprintf('`%s`', $e->getName()); })->join(', ')
		);
	}

}