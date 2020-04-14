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

class EitherTest extends TestCase
{
	
	public function testBasic() {
		
		$hello = new \spitfire\core\parser\lexemes\ReservedWord('hello');
		$world = new \spitfire\core\parser\lexemes\ReservedWord('world');
		
		$buffer1 = new \spitfire\core\parser\TokenBuffer([ $hello ]);
		$buffer2 = new \spitfire\core\parser\TokenBuffer([ $world ]);
		$buffer3 = new \spitfire\core\parser\TokenBuffer([ $hello, $world ]);
		
		$block = new \spitfire\core\parser\parser\Either([
			[ new \spitfire\core\parser\parser\ReservedWordTerminal($hello)],
			[ new \spitfire\core\parser\parser\ReservedWordTerminal($world)]
		]);
		
		$r1 = $block->test($buffer1);
		$r2 = $block->test($buffer2);
		$r3 = $block->test($buffer3);
		
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\ReservedWord::class, $r1[0]);
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\ReservedWord::class, $r2[0]);
		$this->assertInstanceOf(\spitfire\core\parser\lexemes\ReservedWord::class, $r3[0]);
		
	}
	
}
