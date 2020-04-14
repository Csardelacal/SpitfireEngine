<?php namespace spitfire\core\parser;

use BadMethodCallException;
use spitfire\core\parser\StringBuffer;
use spitfire\exceptions\OutOfBoundsException;

/* 
 * The MIT License
 *
 * Copyright 2019 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * The string buffer is intended to make it easy for applications to manipulate
 * the contents of a string. Spitfire uses this to navigate and lex a string that
 * it's tokenizing.
 * 
 * The advantage of a dedicated buffer compared to direct operations on the is that
 * we do not have to edit the string while reading it. When using strings and
 * just consuming them, the system will have to allocate new memory for every
 * operation.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class StringBuffer
{
	
	/**
	 * Indicates where on the string the application has sseked to. By default this
	 * will be the first index (0) of the string.
	 */
	private $pointer = 0;
	
	/**
	 * The string the application is operating on. This string is immutable, this
	 * allows the application to take advantage of the reduced amount of memory
	 * copy operations.
	 */
	private $string;
	
	
	public function __construct(string$string) {
		$this->string = $string;
	}
	
	/**
	 * Moves the pointer to the indicated position. If no argument is given, the 
	 * application will return the current pointer position.
	 * 
	 * @param int $pos
	 * @return int The old pointer position.
	 */
	public function seek(int$pos = null) {
		$old = $this->pointer;
		
		if ($pos !== null) { $this->pointer = $pos; }
		if ($pos < 0) { throw new BadMethodCallException('Seek cannot be negative', 2002191830); }
		if (!isset($this->string[$pos])) { throw new BadMethodCallException('Seeked beyond the end of the string', 2002191834); }
		
		return $old;
	}
	
	public function fastforward($amt = 1) {
		$this->pointer+= $amt;
	}
	
	public function peek($amt = 1, $offset = 0) {
		return substr($this->string, $this->pointer + $offset, $amt);
	}
	
	public function read($amt = 1) {
		$ret = substr($this->string, $this->pointer, $amt);
		$this->pointer += $amt;
		return $ret;
	}
	
	/**
	 * 
	 * 
	 * @param string $char (single character)
	 * @param string $escape (single character)
	 * @return string
	 * @throws OutOfBoundsException
	 */
	public function readUntil($char, $escape = null) {
		$old = $this->pointer;
		$cur = $this->pointer;
		
		while(true) {
			/*
			 * The application is not allowed to read past the end. But this is not
			 * considered normal behavior
			 */
			if (!isset($this->string[$cur])) { 
				throw new OutOfBoundsException('Reached past the end'); 
			} 
			
			if ($escape && $escape == $this->string[$cur]) {
				$cur+= 2;
				continue;
			}
			
			/*
			 * If the current element is the character we're looking for AND not prefixed
			 * by our escape character
			 */
			if ($this->string[$cur] == $char) { 
				break; 
			}
			
			$cur++;
		}
		
		$ret = substr($this->string, $old, $cur - $old);
		$this->pointer = $cur;
		return $ret;
	}
	
	public function slack() {
		return new StringBuffer(substr($this->string, $this->pointer));
	}
	
	public function hasMore() {
		return isset($this->string[$this->pointer ]);
	}
	
}
