<?php namespace tests\spitfire\core\parser\parser;

use PHPUnit\Framework\TestCase;
use spitfire\core\parser\lexemes\Literal;
use spitfire\core\parser\parser\Block;
use spitfire\core\parser\parser\LiteralTerminal;
use spitfire\core\parser\TokenBuffer;

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

class MultipleTest extends TestCase
{
	
	/**
	 * 
	 */
	public function testBasic() {
		
		$tokens = new TokenBuffer([
			new Literal('hello'),
			new Literal('world'),
			new Literal('this'),
			new Literal('is ur'),
			new Literal('captain')
		]);
		
		$block = new Block();
		$block->matches(Block::multiple(new LiteralTerminal()));
		
		$result = $block->test($tokens);
		
		$this->assertInstanceOf(\spitfire\core\parser\parser\ParseTree::class, $result);
		$this->assertEquals(1, count($result->getLeafs()));
		$this->assertEquals(5, count($result->getLeafs()[0]));
	}
	
	public function testBasic2() {
		
		$comma  = new \spitfire\core\parser\lexemes\Symbol(',');
		$tokens = new TokenBuffer([
			new Literal('hello'),
			$comma,
			new Literal('world'),
			$comma,
			new Literal('this'),
			$comma,
			new Literal('is ur'),
			new Literal('captain')
		]);
		
		$block = new Block();
		$block->matches(Block::multiple(new LiteralTerminal(), $comma));
		
		$result = $block->test($tokens);
		
		$this->assertInstanceOf(\spitfire\core\parser\parser\ParseTree::class, $result);
		$this->assertEquals(1, count($result->getLeafs()));
		$this->assertEquals(3, count($result->getLeafs()[0]));
	}
	
}
