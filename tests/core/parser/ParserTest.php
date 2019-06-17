<?php namespace tests\spitfire\core\parser;

use PHPUnit\Framework\TestCase;
use spitfire\core\parser\Block;
use spitfire\core\parser\Identifier;
use spitfire\core\parser\Lexer;
use spitfire\core\parser\Literal;
use spitfire\core\parser\Parser;
use spitfire\core\parser\lexemes\ReservedWord;
use spitfire\core\parser\StringLiteral;
use spitfire\core\parser\Symbol;
use spitfire\core\parser\WhiteSpaceScanner;

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

class ParserTest extends TestCase
{
	
	
	
	public function retestBasicParser() {
		$lit = new Literal('"', '"');
		$pls = new Symbol('+');
		$mns = new Symbol('-');
		$mul = new Symbol('*');
		$div = new Symbol('/');
		$whs = new WhiteSpaceScanner();
		
		$operand = new Block();
		$operation = new Block();
		$operator = new Block();
		$variable = new Block();
		
		$operation->matches($variable, $operator, $operand);
		$operand->matches(Block::either([$operation], [$variable])->name('operand.either.'));
		$operator->matches(Block::either([$div], [$pls], [$mul], [$mns])->name('operator.either.'));
		$variable->matches(Block::either([new StringLiteral()], [new Identifier()])->name('variable.either.'));
		
		$operand->name = 'operand';
		$operator->name = 'operator';
		$operation->name = 'operation';
		$variable->name = 'variable';
		
		$lex = new Lexer($lit, $pls, $mns, $mul, $div, $whs);
		$parser = new Parser($operation);
		
		echo implode(',', $parser->parse($lex->tokenize('3 * myval + 5 / 6')));
		
		
	}
	
	public function testBasicParser2() {
		$and = new ReservedWord('AND');
		$or  = new ReservedWord('OR');
		$lit = new Literal('"', '"');
		$pop = new Symbol('(');
		$pcl = new Symbol(')');
		$bop = new Symbol('[');
		$bcl = new Symbol(']');
		$com = new Symbol(',');
		$dot = new Symbol('.');
		$whs = new WhiteSpaceScanner();
		
		$statement = new Block();
		$expression = new Block();
		$binaryand = new Block();
		$binaryor  = new Block();
		$rule      = new Block();
		$fnname    = new Block();
		$arguments = new Block();
		$options   = new Block();
		$additional= new Block();
		
		
		
		$binaryand->name = 'AND';
		$binaryor->name = 'OR';
		$statement->name = 'statement';
		$expression->name = 'expression';
		$rule->name = 'rule';
		$fnname->name = 'function identifier';
		$arguments->name = 'arguments';
		$options->name = 'options';
		
		
		
		$statement->matches(Block::either([$binaryand], [$binaryor],  [$expression])->name('statement.either'));
		$binaryand->matches($expression, $and, $statement);
		$binaryor->matches($expression, $or, $statement);
		$expression->matches(Block::either([$rule], [$pop, $statement, $pcl])->name('expression.either.'));
		$rule->matches($fnname, $pop, $arguments, $pcl);
		$fnname->matches(new Identifier(), $dot, new Identifier());
		$arguments->matches(new Identifier(), Block::optional($options), Block::optional($arguments));
		
		$options->matches($bop, new StringLiteral(), Block::optional($additional), $bcl);
		$additional->matches($com, new StringLiteral(), Block::optional($additional));
		
		
		
		
		$lex = new Lexer($and, $or, $lit, $pop, $pcl, $com, $dot, $whs, $bop, $bcl);
		
		
		$string = '(GET.input(string length[10, 24] not["detail"]) OR POST.other(positive number)) AND POST.test(file) OR t.s(something) AND t.i(s else["else"])';
		$parser = new Parser($statement);
		
		$res = $parser->parse($lex->tokenize($string));
		
		$this->assertEquals(true, is_array($res));
		$this->assertArrayHasKey(0, $res);
		$this->assertArrayNotHasKey(1, $res);
		
	}/**/
	
}
