<?php namespace tests\spitfire\validation\rules;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
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

use PHPUnit\Framework\TestCase;
use spitfire\ast\Scope;
use spitfire\validation\parser\Parser;

class InArrayTest extends TestCase
{
	
	
	public function testIn() {
		
		$rule = new \spitfire\validation\rules\InValidationRule(['a', 'b', 'c'], 'Invalid value for in');
		
		$this->assertInstanceOf(\spitfire\validation\ValidationError::class, $rule->test('d'));
		$this->assertEquals(false, $rule->test(null));
	}
	
	public function testExpression() {
		
		$good = new Scope();
		$good->set('GET', ['test' => 'c d']);
		
		$bad = new Scope();
		$bad->set('GET', ['test' => 'i']);
		
		$empty = new Scope();
		$empty->set('GET', ['test' => null]);
		
		$validator = (new Parser())->parse('GET.test(string in["a", "b", "c d"])');
		
		$this->assertEquals(true, empty($validator->resolve($good)));
		$this->assertEquals(true, empty($validator->resolve($empty)));
		$this->assertEquals(false, empty($validator->resolve($bad)));
	}
	
}