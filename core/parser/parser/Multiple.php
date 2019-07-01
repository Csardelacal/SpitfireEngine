<?php namespace spitfire\core\parser\parser;

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

class Multiple
{
	
	private $block;
	
	/**
	 * 
	 */
	public function __construct($a) {
		$this->block = new Block();	
		$this->block->matchesArray($a);
	}
	
	
	public function test($tokens) {
		$_ret = [];
		echo 'Testing multiple', PHP_EOL;
		
		while ($res = $this->block->test($tokens)) {
			$_ret[] = array_shift($res);
			$tokens = $res;
		} 
		
		if (empty($_ret)) {
			echo 'Failed multiple', PHP_EOL;
		}
		else {
			echo 'Found ', count($_ret), PHP_EOL;
		}
		
		return !empty($_ret)? array_merge([new ParseTree($this, $_ret)], $tokens) : false;
	}
	
	public function __toString() {
		return $this->block . '+';
	}
	
}
