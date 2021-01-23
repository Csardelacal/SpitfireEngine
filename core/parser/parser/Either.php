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
 * Allows an abstract syntax tree parser builder to create a parser that accepts 
 * multiple syntax for a certain expression.
 * 
 * This is an anonymous block, meaning it does not provide any logic to interpret
 * the results from the parsed syntax. The block above it will be required to make
 * a distinction between the results given by this block.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Either
{
	
	/**
	 * The blocks that this relies on to perform the parsing. Please note that if 
	 * the application has multiple matches for a tokenbuffer, the first match
	 * will be considered.
	 *
	 * @var \spitfire\collection\DefinedCollection < Block >
	 */
	private $blocks;
	
	/**
	 * Create an either-block. This block will accept rules for multiple blocks
	 * and whenever it's test() method is invoked it will attempt to match one of
	 * them.
	 * 
	 * @param mixed $a Array of arrays containing terminals or blocks
	 */
	public function __construct($a) {
		$this->blocks = collect($a)->each(function ($e) { 
			$block = new Block();
			$block->matchesArray($e);
			return $block;
		} );	
	}
	
	/**
	 * Unlike "regular" blocks, either will return an array so that the parent can
	 * merge it into the other blocks.
	 * 
	 * Just like regular blocks, this mechanism will consume the buffer, which means
	 * that it will be modified if the parse succeeds.
	 * 
	 * @param \spitfire\core\parser\TokenBuffer $tokens A tokenbuffer to consume
	 * @return boolean
	 */
	public function test($tokens) {
		$blocks = clone($this->blocks);
		
		foreach ($blocks as $block) {
			$res = $block->test($tokens);
			
			if ($res) { 
				/*
				 * Depending on whether the descendant returned an array or a ParseTree,
				 * we will strip it out so it can be flattened into the parent.
				 * 
				 * This means that these anonymous blocks are not referred to when evaluating
				 * the tree and are transparent to the developer. This means that these
				 * anon bocks are also unable to provide any functionality and therefore
				 * need to have their logic shifted to their parent.
				 * 
				 * For example, the code 
				 * block(either(private, public), function, reservedword...)
				 * 
				 * Will have to have the logic to determine whether the first block is
				 * either private or public.
				 */
				return is_array($res)? $res : $res->getLeafs(); 
			}
		}
		
		return false;
	}
	
	public function children() {
		return $this->blocks;
	}
	
	public function __toString() {
		return sprintf('either:');
	}
	
}
