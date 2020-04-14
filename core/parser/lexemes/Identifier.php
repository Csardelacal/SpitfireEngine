<?php namespace spitfire\core\parser\lexemes;

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
 * An identifier is a lexeme that identifies a user defined variable or function
 * name. In this context, anything that is not white-space, and matches a standard
 * function / variable naming expression will be used.
 * 
 * Please note that the system requires all identifiers to have valid names. In 
 * case your application contains identifiers like fully qualified names (\my\ClassName)
 * you will be required to construct this by indicating to the system that a class
 * identifier can be constructed by adding multiple identifiers together.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Identifier implements LexemeInterface
{
	
	private $name;
	
	/**
	 * Creates the identifier.
	 * 
	 * @param string $name
	 */
	public function __construct($name = null) {
		$this->name = $name;
	}
	
	/**
	 * Returns the content of the identifier. This will be the name of the variable
	 * / function / class / whatever inside your language.
	 * 
	 * @return string
	 */
	public function getBody(): string {
		return $this->name;
	}
	
	/**
	 * In the event of printing out the Identifier, we're printing it as a representation
	 * that will make sense within the other output of the lexer.
	 * 
	 * @return string
	 */
	public function __toString() {
		return sprintf('val(%s)', strval($this->name));
	}

}
