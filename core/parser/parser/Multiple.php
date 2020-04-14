<?php namespace spitfire\core\parser\parser;

use spitfire\core\parser\TokenBuffer;

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
 * A multiple block allows an application to consume multiple blocks in a greedy
 * fashion, it can be used to construct statements like or statements, where an
 * expression is followed by multiple combinations of pipes and expressions.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Multiple extends Block
{
	
	/**
	 * The multiple block will return a "shallow" array of the results of multiple
	 * iterations of the same Block being executed on a token buffer.
	 * 
	 * Basically, this block will consume from the buffer until the block it wraps
	 * can no longer match.
	 * 
	 * @param TokenBuffer $tokens
	 * @return mixed
	 */
	public function test(TokenBuffer$tokens) {
		$_ret = [];
		
		while ($res = parent::test($tokens)) {
			$_ret[] = is_array($res)? $res : $res->getLeafs();
		} 
		
		return !empty($_ret)? $_ret : false;
	}
	
	public function __toString() {
		return parent::__toString() . '+';
	}
	
}
