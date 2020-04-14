<?php namespace spitfire\core\parser\scanner;

use spitfire\core\parser\lexemes\LexemeInterface;
use spitfire\core\parser\lexemes\Literal;
use spitfire\core\parser\scanner\ScannerModuleInterface;
use spitfire\core\parser\StringBuffer;

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


class LiteralScanner implements ScannerModuleInterface
{
	
	private $open;
	
	private $close;
	
	
	public function __construct($open, $close) {
		$this->open = $open;
		$this->close = $close;
	}

	public function in(StringBuffer $buffer): ?LexemeInterface {
		/**
		 * Check if the opening is present
		 */
		if ($buffer->peek(strlen($this->open)) !== $this->open) {
			return null;
		}
		
		$pos  = $buffer->seek();
		
		try {
			/*
			 * Read the first character from thee sequence, we already know that it's 
			 * the appropriate character
			 */
			$buffer->read();
			$_ret = new Literal(stripslashes($buffer->readUntil($this->close, '\\')));
			
			/*
			 * The buffer doesn't consume the closing character when reading up to the 
			 * indicated character. We need to fetch it from the buffer
			 */
			$buffer->read();
			return $_ret;
		} 
		catch (\spitfire\exceptions\OutOfBoundsException$ex) {
			/*
			 * Place the buffer back to it's original position
			 */
			$buffer->seek($pos);
			
			/*
			 * If the buffer is empty and we did not receive a return, then the string
			 * is invalid.
			 */
			return null;
		}
		
	}

}
