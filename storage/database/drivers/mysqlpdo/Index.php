<?php namespace spitfire\storage\database\drivers\mysqlpdo;

use spitfire\storage\database\Field;
use spitfire\storage\database\IndexInterface;

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

class Index implements IndexInterface
{
	
	private $fields;
	
	private $name;
	
	private $primary;
	
	private $unique;
	
	public function __construct($fields, $name, $primary, $unique) {
		$this->fields = $fields;
		$this->name = $name;
		$this->primary = $primary;
		$this->unique = $unique;
	}
	
	public function getFields() {
		return $this->fields;
	}

	public function getName(): string {
		return $this->name;
	}

	public function isPrimary(): bool {
		return !!$this->primary;
	}

	public function isUnique(): bool {
		return $this->primary || $this->unique;
	}
	
	public function definition() {
		return sprintf(
			'%s %s ON %s',
			$this->primary? 'PRIMARY KEY' : ($this->unique? 'UNIQUE INDEX' : 'INDEX'),
			$this->getName()? : '',
			implode(', ', $this->getFields())
		);
	}

}