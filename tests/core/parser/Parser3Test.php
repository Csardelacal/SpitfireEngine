<?php namespace tests\spitfire\core\parser;

use Exception;
use PHPUnit\Framework\TestCase;
use spitfire\core\parser\lexemes\Identifier;
use spitfire\core\parser\lexemes\Literal as Literal2;
use spitfire\core\parser\lexemes\ReservedWord;
use spitfire\core\parser\lexemes\Symbol;
use spitfire\core\parser\Lexer;
use spitfire\core\parser\Literal;
use spitfire\core\parser\parser\Block;
use spitfire\core\parser\parser\IdentifierTerminal;
use spitfire\core\parser\parser\LiteralTerminal;
use spitfire\core\parser\parser\Parser;
use spitfire\core\parser\parser\ParseTree;
use spitfire\core\parser\printer\Printer;
use spitfire\core\parser\scanner\IdentifierScanner;
use spitfire\core\parser\scanner\IntegerLiteralScanner;
use spitfire\core\parser\scanner\LiteralScanner;
use spitfire\core\parser\scanner\WhiteSpaceScanner;
use spitfire\core\parser\Scope;
use spitfire\exceptions\PrivateException;
use function collect;
use function console;

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

class Parser3Test extends TestCase
{
	
	
	
	public function testBasicParser() {
		$and = new ReservedWord('AND');
		$or  = new ReservedWord('OR');
		$lit = new LiteralScanner('"', '"');
		$pop = new Symbol('(');
		$pcl = new Symbol(')');
		$bop = new Symbol('[');
		$bcl = new Symbol(']');
		$com = new Symbol(',');
		$ran = new Symbol('>');
		$dot = new Symbol('.');
		$whs = new WhiteSpaceScanner();
		$ids = new IdentifierScanner();
		$int = new IntegerLiteralScanner();
		
		$statement = new Block();
		$expression = new Block();
		$binaryand = new Block();
		$binaryor  = new Block();
		$rule      = new Block();
		$fnname    = new Block();
		$arguments = new Block();
		$options   = new Block();
		
		
		$statement->matches($binaryor);
		$binaryor->matches($binaryand, Block::optional(Block::multiple($or, $binaryand)));
		$binaryand->matches($expression, Block::optional(Block::multiple($and, $expression)));
		
		$expression->matches(Block::either([$rule], [$pop, $statement, $pcl]));
		$rule->matches($fnname, $pop, $arguments, $pcl);
		$fnname->matches(new IdentifierTerminal(), $dot, new IdentifierTerminal());
		$arguments->matches(Block::multiple(new IdentifierTerminal(), Block::optional($options)));
		$options->matches($bop, new LiteralTerminal(), Block::optional(Block::multiple($com, new LiteralTerminal())), $bcl);
		
		
		$lex = new Lexer($and, $or, $lit, $pop, $pcl, $com, $dot, $whs, $bop, $bcl, $ids, $int, $ran);
		
		
		$string = 'GET.input(string length[5, 24] not["detail"]) OR POST.other(positive number)';
		$parser = new Parser($statement);
		
		$statement->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			return $leafs[0]->resolve($scope);
		});
		
		$binaryor->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			$result = $leafs[0]->resolve($scope);
			
			if (!$leafs[1]) { return $result; }
			
			foreach ($leafs[1][0] as $op) {
				$result = $result || $op[1]->resolve($scope);
			}
			
			return $result;
		});
		
		$binaryand->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			$result = $leafs[0]->resolve($scope);
			
			if (!$leafs[1]) { return $result; }
			
			foreach ($leafs[1][0] as $op) {
				$result = $result && $op[1]->resolve($scope);
			}
			
			return $result;
		});
		
		$expression->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs()[0];
			if (count($leafs) == 1) {
				return $leafs[0]->resolve($scope);
			}
			
			if (count($leafs) == 3) {
				return $leafs[1]->resolve($scope);
			}
			
			throw new Exception('Impossible condition reached');
		});
		
		$rule->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			$fn = $leafs[0]->resolve($scope);
			$args = $leafs[2]->resolve($scope);
			
			$payload = $scope->get($fn[0])[$fn[1]]?? null;
			$pass = true;
			
			foreach ($args as $arg) {
				$pass = $pass && $arg($payload);
			}
			
			return $pass;
		});
		
		$fnname->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			return [$leafs[0]->getBody(), $leafs[2]->getBody()];
		});
		
		$arguments->does(function ($leaf, $scope) {
			$fns = [
				'not'    => function ($args = []) { return function ($payload) use ($args) { return $payload !== $args[0]; }; },
				'string' => function ($args = []) { return function ($payload) use ($args) { return is_string($payload); }; },
				'positive' => function ($args = []) { return function ($payload) use ($args) { return $payload > 0; }; },
				'number' => function ($args = []) { return function ($payload) use ($args) { return is_numeric($payload); }; },
				'file'   => function ($args = []) { return function ($payload) use ($args) { return $payload === 'file'; }; },
				'something'   => function ($args = []) { return function ($payload) use ($args) { return $payload === 'something'; }; },
				's'   => function ($args = []) { return function ($payload) use ($args) { return $payload === 's'; }; },
				'else'   => function ($args = []) { return function ($payload) use ($args) { return $payload === 'else'; }; },
				'length' => function ($args = []) { return function ($payload) use ($args) { return strlen($payload) > $args[0] && strlen($payload) < $args[1] ; }; }
			];
			
			$_ret = collect($leaf->getLeafs()[0])->each(function ($e) use ($fns, $scope) {
				list($argument, $options) = $e;
				$params = $options? collect($options)->each(function ($e) use ($scope) { return $e->resolve($scope); })->toArray() : [[]];
				return $fns[$argument->getBody()]($params[0]);
			})->toArray();
			
			return $_ret;
		});
		
		$options->does(function ($leaf, $scope) {
			$raw = $leaf->getLeafs();
			
			$clean = [$raw[1]->getBody()];
			$modifiers = $raw[2];
			
			//$options->matches($bop, new LiteralTerminal(), Block::optional(Block::multiple($com, new LiteralTerminal())), $bcl);
			
			if (!empty($modifiers)) {
				foreach ($modifiers[0] as $modifier) {
					$clean[] = $modifier[1]->getBody();
				}
			}
			
			return $clean;
		});
		
		$res = $parser->parse($lex->tokenize($string));
		
		$this->assertEquals(true, $res instanceof ParseTree);
		$this->assertEquals(1, count($res->getLeafs()));
		
		/*
		 * Create a context with the variables that we want to have within the 
		 * expression's scope.
		 */
		$scope = new Scope();
		$scope->set('GET', ['input' => 'testtesttest']);
		$scope->set('POST', ['other' => -35]);
		
		$scope2 = new Scope();
		$scope2->set('GET', ['input' => 'detail']);
		$scope2->set('POST', ['other' => -35]);
		
		$scope3 = new Scope();
		$scope3->set('GET', ['input' => 'detail']);
		$scope3->set('POST', ['other' => 35]);
		
		$this->assertEquals(true, $res->resolve($scope));
		$this->assertEquals(false, $res->resolve($scope2));
		$this->assertEquals(true, $res->resolve($scope3));
	}/**/
	
}
