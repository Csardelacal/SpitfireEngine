<?php namespace tests\spitfire\core;

use PHPUnit\Framework\TestCase;

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


class EnvironmentTest extends TestCase
{
	private $env;
	
	public function setUp() : void {
		$this->env = new \spitfire\core\Environment('test_' . time());
		$this->env->set('test.123', 'abc');
		$this->env->set('test.abc', '123');
		$this->env->set('test.245', 'bcd');
	}
	
	public function testSubTree() {
		$subtree = $this->env->subtree('test.');
		$this->assertArrayHasKey('abc', $subtree);
		$this->assertArrayHasKey('123', $subtree);
		$this->assertEquals('123', $subtree['abc']);
	}
	
}