<?php

use PHPUnit\Framework\TestCase;
use spitfire\core\parser\scanner\IdentifierScanner;
use spitfire\core\parser\StringBuffer;

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

/*
 * The identifier scanner must be able to consume identifiers from a string without
 * taking in too much or too little
 */
class IdentifierScannerTest extends TestCase
{
	
	public function testBasic() {
		$buffer = new StringBuffer('hello world');
		$scanner = new IdentifierScanner();
		
		$this->assertEquals('hello', $buffer->peek(5));
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\Identifier::class, $scanner->in($buffer));
		$this->assertEquals(' world', $buffer->peek(6));
	}
	
	public function testHyphens() {
		$buffer = new StringBuffer('hello-world');
		$scanner = new IdentifierScanner();
		
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\Identifier::class, $scanner->in($buffer));
		$this->assertEquals('-world', $buffer->peek(6));
	}
	
	public function testParentheses() {
		$buffer = new StringBuffer('hello(world)');
		$scanner = new IdentifierScanner();
		
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\Identifier::class, $scanner->in($buffer));
		$this->assertEquals('(world', $buffer->peek(6));
	}
	
	public function testUnderscore() {
		$buffer = new StringBuffer('hello_world');
		$scanner = new IdentifierScanner();
		
		$this->assertEquals('hello', $buffer->peek(5));
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\Identifier::class, $scanner->in($buffer));
		$this->assertEquals('', $buffer->peek(6));
	}
	
}
