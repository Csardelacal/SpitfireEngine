<?php namespace tests\spitfire\core\parser\parser;

use PHPUnit\Framework\TestCase;

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

class BlockTest extends TestCase
{
	
	/**
	 * This test simulates the behavior the application would display when given 
	 * a (theoretical) basic function declaration like `function myfun(myval)` and
	 * measures whether it properly parsed it.
	 */
	public function testTest() {
		
		$fn  = new \spitfire\core\parser\lexemes\ReservedWord('function');
		$pop = new \spitfire\core\parser\lexemes\Symbol('(');
		$pcl = new \spitfire\core\parser\lexemes\Symbol(')');
		
		$buffer = new \spitfire\core\parser\TokenBuffer([
			$fn,
			new \spitfire\core\parser\lexemes\Identifier('myfun'),
			$pop,
			new \spitfire\core\parser\lexemes\Identifier('myval'),
			$pcl
		]);
		
		$block = new \spitfire\core\parser\parser\Block();
		$block->matches(
			new \spitfire\core\parser\parser\ReservedWordTerminal($fn),
			new \spitfire\core\parser\parser\IdentifierTerminal(),
			new \spitfire\core\parser\parser\SymbolTerminal($pop),
			new \spitfire\core\parser\parser\IdentifierTerminal(),
			new \spitfire\core\parser\parser\SymbolTerminal($pcl)
		);
		
		$result = $block->test($buffer);
		$this->assertInstanceOf(\spitfire\core\parser\parser\ParseTree::class, $result);
		
		$leafs = $result->getLeafs();
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\ReservedWord::class, $leafs[0]);
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\Identifier::class, $leafs[1]);
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\Symbol::class, $leafs[2]);
		
	}
	
}
