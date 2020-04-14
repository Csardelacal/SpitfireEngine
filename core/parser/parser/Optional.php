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

/**
 * The optional shallow-block allows the application to define a segment of the 
 * buffer that may be present but may also not be. A typical example for this 
 * would be the type modifiers for PHP functions where:
 * 
 * reserved(function) identifier symbol(() optional(modifier(type)) modifier(variablename) optional(multiple(...)) symbol())
 * 
 * We could use these blocks to identify that the type may, or may not, be present
 * in your expression.
 */
class Optional extends Block
{
	
	/**
	 * Test whether the buffer contains the block this inherits from, unlike a 
	 * regular block, an empty result will not lead to the parser failing, but the
	 * block being skipped and it's result set to null.
	 * 
	 * @param type $tokens
	 * @return boolean
	 */
	public function test($tokens) {
		$res = parent::test($tokens);
		
		/*
		 * This is a shallow-block, meaning that the leafs are passed as an array 
		 * to the parent instead of a parse tree.
		 */
		if ($res) {
			return $res->getLeafs(); 
		}
		
		/*
		 * Unlike a regular block, this result will be null.
		 */
		return null;
	}
	
	public function __toString() {
		return 'optional';
	}
	
}
