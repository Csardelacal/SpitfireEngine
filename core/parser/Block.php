<?php namespace spitfire\core\parser;

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

class Block
{
	private $matches = [];
	
	public $name = 'undefined';
	
	public function matches() {
		$args = func_get_args();
		$this->matches = $args;
	}
	
	public function matchesArray($a) {
		$this->matches = $a;
	}
	
	public function test($tokens) {
		
		$orig  = $tokens;
		$found = [];
		
		/*
		 * Loop over the matches and see if we can extract anything of value
		 */
		foreach ($this->matches as $rule) {
			
			if ($rule instanceof Optional) { 
				$t = $rule->test($tokens); 
				if ($t) { 
					$found[] = array_shift($t);
					$tokens = $t;
				}
				
			} #Eithers are single children
			
			/*
			 * If another block is the child, we will first descend into it. If it
			 * returns false, then the block did not match, and therefore ours won't
			 * either.
			 * 
			 * On the other hand, if the block did match, then we will put it's result
			 * into found, and continue parsing.
			 */
			if ($rule instanceof Block || $rule instanceof Either) {
				
				$r = $rule->test($tokens);
				
				if ($r) { $tokens = $r; }
				else { return false; }
				
				$found[] = array_shift($tokens);
			}
			
			if ($rule instanceof LexemeInterface) {
				if ($rule === reset($tokens)) { $found[] = array_shift($tokens); }
				else { return false; }
			}
			
			if ($rule instanceof Identifier) {
				if (is_string(reset($tokens))) { $found[] = new Identifier(array_shift($tokens)); }
				else { return false; }
			}
			
			if ($rule instanceof StringLiteral) {
				if (reset($tokens) instanceof Literal) { $found[] = new StringLiteral(array_shift($tokens)->getBody()); }
				else { return false; }
			}
		}
		
		
		$r = array_merge([new ParseTree($this, $found)], $tokens);
		return $r;
	}
	
	public function __toString() {
		return implode(',', $this->matches);
	}
	
	public static function either() {
		$args = func_get_args();
		return new Either($args);
	}
	
	public static function optional() {
		$args = func_get_args();
		return new Optional($args);
	}
}