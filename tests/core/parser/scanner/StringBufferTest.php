<?php namespace tests\spitfire\core\parser\scanner;

use BadMethodCallException;
use PHPUnit\Framework\TestCase;
use spitfire\core\parser\StringBuffer;
use spitfire\exceptions\OutOfBoundsException;

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

class StringBufferTest extends TestCase
{
	
	public function testPeek() {
		
		$buffer = new StringBuffer('Hello world');
		
		$this->assertEquals('Hello', $buffer->peek(5));
		$this->assertEquals('H', $buffer->peek());
		$this->assertEquals('world', $buffer->peek(5, 6));
	}
	
	public function testSeekNegative() {
		$buffer = new StringBuffer('Hello world');
		$this->expectException(BadMethodCallException::class);
		$buffer->seek(-3);
	}
	
	public function testSeekTooFar() {
		$buffer = new StringBuffer('Hello world');
		$this->expectException(BadMethodCallException::class);
		$buffer->seek(300);
	}
	
	public function testRead() {
		
		$buffer = new StringBuffer('Hello world');
		
		$this->assertEquals('Hello', $buffer->read(5));
		$this->assertEquals(' ', $buffer->read());
		$this->assertEquals('world', $buffer->read(5));
	}
	
	public function testReaduntil() {
		$buffer = new StringBuffer('Hello world');
		$this->assertEquals('Hello worl', $buffer->readUntil('d'));
	}
	
	public function testReaduntilWithUPB() {
		$buffer = new StringBuffer('Helo world');
		$this->assertEquals('Helo w', $buffer->readUntil('o', 'l'));
	}
	
	public function testReaduntilWithUPB2() {
		$buffer = new StringBuffer('Hello world"');
		$this->assertEquals('Hello world', $buffer->readUntil('"', '\\'));
	}
	
	public function testReaduntilWithUPB3() {
		$buffer = new StringBuffer('Hello \"world"');
		$this->assertEquals('Hello \"world', $buffer->readUntil('"', '\\'));
	}
	
	public function testReaduntilWithUPB4() {
		$buffer = new StringBuffer('Hello \\\\"world"');
		$this->assertEquals('Hello \\\\', $buffer->readUntil('"', '\\'));
	}
	
	public function testReaduntilInexistent() {
		$buffer = new StringBuffer('Hello world');
		$this->expectException(OutOfBoundsException::class);
		$this->assertEquals('Hello w', $buffer->readUntil('z'));
	}
	
}
