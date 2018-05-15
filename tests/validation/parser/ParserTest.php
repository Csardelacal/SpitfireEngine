<?php namespace tests\spitfire\validation\parser;

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

class ParserTest extends TestCase
{
	
	
	public function testParseLiteral() {
		
		$end    = time() + 3;
		$count  = 0;
		
		do {
			$string = 'GET#input(string length[10,24] not["detail"]) OR POST#other(positive number) AND POST#something(required) AND GET#another(required)';

			$pp = new \spitfire\validation\parser\preprocessor\Preprocessor();
			$result = $pp->prepare($string);
			$result->tokenize();

			$or = new \spitfire\validation\parser\logicparser\OrProcessor();
			$or->run($result);

			$and = new \spitfire\validation\parser\logicparser\AndProcessor();
			$and->run($result);
			$count++;
		}
		while (time() < $end);
		
		echo $result, PHP_EOL;
		echo $count, PHP_EOL;
		die();
	}
}