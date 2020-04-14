<?php namespace tests\spitfire\core\parser;

use Exception;
use PHPUnit\Framework\TestCase;
use spitfire\core\parser\lexemes\Identifier;
use spitfire\core\parser\lexemes\Literal;
use spitfire\core\parser\lexemes\Symbol;
use spitfire\core\parser\Lexer;
use spitfire\core\parser\parser\Block;
use spitfire\core\parser\parser\IdentifierTerminal;
use spitfire\core\parser\parser\LiteralTerminal;
use spitfire\core\parser\parser\Parser;
use spitfire\core\parser\parser\ParseTree;
use spitfire\core\parser\parser\SymbolTerminal;
use spitfire\core\parser\scanner\IdentifierScanner;
use spitfire\core\parser\scanner\IntegerLiteralScanner;
use spitfire\core\parser\scanner\LiteralScanner;
use spitfire\core\parser\scanner\WhiteSpaceScanner;
use spitfire\core\parser\Scope;

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

class Parser2Test extends TestCase
{
	
	
	
	public function testBasicParser() {
		$lit = new LiteralScanner('"', '"');
		$pls = new Symbol('+');
		$mns = new Symbol('-');
		$mul = new Symbol('*');
		$div = new Symbol('/');
		$not = new Symbol('!');
		$pop = new Symbol('(');
		$pcl = new Symbol(')');
		$whs = new WhiteSpaceScanner();
		$ids = new IdentifierScanner();
		$int = new IntegerLiteralScanner();
		
		$expression     = new Block();
		$addition       = new Block();
		$multiplication = new Block();
		$unary          = new Block();
		$primary        = new Block();
		
		$expression->matches($addition);
		$addition->matches($multiplication, Block::optional(Block::multiple(Block::either([$pls], [$mns]), $multiplication)));
		
		$multiplication->matches(
			$unary, 
			Block::optional(Block::multiple(Block::either([new SymbolTerminal($mul)], [new SymbolTerminal($div)]), $unary))
		);
		
		$unary->matches(
			Block::either([new SymbolTerminal($not), $primary], [$primary])
		);
		
		$primary->matches(Block::either(
			[new IdentifierTerminal()], 
			[new LiteralTerminal()], 
			[new SymbolTerminal($pop), $expression, new SymbolTerminal($pcl)]
			)
		);
		
		$lex = new Lexer($lit, $pls, $mns, $mul, $div, $whs, $ids, $int, $not, $pop, $pcl);
		$parser = new Parser($expression);
		
		$res = $parser->parse($lex->tokenize('3 * myval + 5 / (1 + two * two) + 5'));
		
		$this->assertEquals(true, $res instanceof ParseTree);
		$this->assertEquals(1, count($res->getLeafs()));
		
		
		$expression->does(function ($leaf, $scope) {
			$previous = $leaf->getLeafs();
			return $previous[0]->resolve($scope);
		});
		
		$addition->does(function ($leaf, $scope) {
			list($left, $right) = $leaf->getLeafs();
			
			$left = $left->resolve($scope);
			
			if ($right === null) {
				return $left;
			}
			
			foreach ($right[0] as $next) {
				list($operator, $operand) = $next;
				$operand = $operand->resolve($scope);
				
				if ($operator[0]->getBody() === '+') { $left+= $operand; }
				else                                 { $left-= $operand; }
			}
			
			return $left;
		});
		
		$multiplication->does(function ($leaf, $scope) {
			list($left, $right) = $leaf->getLeafs();
			
			$left = $left->resolve($scope);
			
			if ($right === null) {
				return $left;
			}
			
			foreach ($right[0] as $next) {
				list($operator, $operand) = $next;
				$operand = $operand->resolve($scope);
				
				if ($operator[0]->getBody() === '*') { $left*= $operand; }
				else                                 { $left/= $operand; }
			}
			
			return $left;
		});
		
		$unary->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			
			if (count($leafs[0]) == 2) {
				return !$leafs[0][1]->resolve($scope);
			} else {
				return $leafs[0][0]->resolve($scope);
			}
		});
		
		$primary->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs()[0];
			
			switch(count($leafs)) {
				case 1: // literal | identifier
					if ($leafs[0] instanceof Literal) { return $leafs[0]->getBody(); }
					if ($leafs[0] instanceof Identifier) {
						return $scope->get($leafs[0]->getBody()); 
					}
					
					throw new Exception('Unsupported operand');
				case 3: // (expression)
					return $leafs[1]->resolve($scope);
			}
		});
		
		//echo $res;
		//echo PHP_EOL;
		$scope = new Scope();
		$scope->set('myval', 10);
		$scope->set('two', 2);
		$out = $res->resolve($scope);
		
		$this->assertEquals(36, $out);
	}
	
}
