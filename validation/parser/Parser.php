<?php namespace spitfire\validation\parser;

use Closure;
use spitfire\core\parser\lexemes\ReservedWord;
use spitfire\core\parser\lexemes\Symbol;
use spitfire\core\parser\Lexer;
use spitfire\core\parser\parser\Block;
use spitfire\core\parser\parser\IdentifierTerminal;
use spitfire\core\parser\parser\LiteralTerminal;
use spitfire\core\parser\parser\Parser as ParserCore;
use spitfire\core\parser\parser\ParseTree;
use spitfire\core\parser\scanner\IdentifierScanner;
use spitfire\core\parser\scanner\IntegerLiteralScanner;
use spitfire\core\parser\scanner\LiteralScanner;
use spitfire\core\parser\scanner\WhiteSpaceScanner;
use spitfire\exceptions\PrivateException;
use spitfire\validation\rules\EmptyValidationRule;
use spitfire\validation\rules\FilterValidationRule;
use spitfire\validation\rules\InValidationRule;
use spitfire\validation\rules\LengthValidationRule;
use spitfire\validation\rules\NotValidationRule;
use spitfire\validation\rules\PositiveNumberValidationRule;
use spitfire\validation\rules\TypeNumberValidationRule;
use spitfire\validation\rules\TypeStringValidationRule;
use function __;
use function collect;

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

/**
 * This parser allows a developer to configure the application to validate the 
 * incoming data using expressions inside the docblocks (or any similar mechanism)
 * 
 * @todo Consider a more elaborate mechanism for rule creation. Generally speaking,
 * the creation of rules should be rather simple. Since this element only interfaces
 * with the actual rules. But maybe it could be interesting.
 */
class Parser
{
	
	
	/**
	 * Contains a list of callable items that allow your application to instance 
	 * ValidationRules from the expressions provided.
	 *
	 * @var Closure[]
	 */
	private $rules = [];
	
	/**
	 * Creates a new parser for validation expressions. These expressions allow to
	 * quickly provide the application with validation for input.
	 */
	public function __construct() {
		
		#Create the default rules
		$this->rules['string'] = function() { return new TypeStringValidationRule('Accepts only strings'); };
		$this->rules['email']  = function() { return new FilterValidationRule(FILTER_VALIDATE_EMAIL, 'Invalid email provided'); };
		$this->rules['url']    = function() { return new FilterValidationRule(FILTER_VALIDATE_URL, 'Invalid email provided'); };
		$this->rules['positive']=function() { return new PositiveNumberValidationRule('Value must be a positive number'); };
		$this->rules['number'] = function() { return new TypeNumberValidationRule('Value must be a number'); };
		$this->rules['required']=function() { return new EmptyValidationRule('Value is required. Cannot be empty'); };
		$this->rules['not']    = function($value) { return new NotValidationRule($value, sprintf('Value "%s" is not allowed', $value)); };
		$this->rules['in']     = function() { return new InValidationRule(func_get_args(), sprintf('Value must be one of (%s)', __(implode(', ', func_get_args())))); };
		$this->rules['length'] = function($min, $max = null) { return new LengthValidationRule($min, $max, sprintf('Field length must be between %s and %s characters', $min, $max)); };
	}
	
	/**
	 * Parses the expression, extracting the Validator used to ensure that the 
	 * data provided by the app's user is correct.
	 * 
	 * @param string $string
	 * @return ParseTree
	 */
	public function parse($string) {
		
		/*
		 * Defines all the special characters, identifiers, literals and reserved 
		 * words that the parser is expected to support.
		 */
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
		$hb  = new Symbol('#');
		$whs = new WhiteSpaceScanner();
		$ids = new IdentifierScanner();
		$int = new IntegerLiteralScanner();
		
		/*
		 * Initializes the blocks. Blocks allow the application to combine a series
		 * of identifiers and reserved words into a grammar.
		 */
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
		$fnname->matches(new IdentifierTerminal(), Block::either([$dot], [$hb]), new IdentifierTerminal());
		$arguments->matches(Block::multiple(new IdentifierTerminal(), Block::optional($options)));
		$options->matches($bop, new LiteralTerminal(), Block::optional(Block::multiple($com, new LiteralTerminal())), $bcl);
		
		/*
		 * Creates the lexer. The lexer will receive a character buffer and determine
		 * which characters are part of which identifier, reserved word, symbol, etc.
		 */
		$lex = new Lexer($and, $or, $lit, $pop, $pcl, $com, $dot, $hb, $whs, $bop, $bcl, $ids, $int, $ran);
		$parser = new ParserCore($statement);
		
		/*
		 * At it's core, a validation expression is a statement, a statement contains
		 * only one (and only one) binary OR expression that it resolves.
		 * 
		 * While this may seem counter-intuitive, it allows the system to create complex
		 * grammar trees with this being the recurring theme.
		 */
		$statement->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			return $leafs[0]->resolve($scope);
		});
		
		/*
		 * Define the binary OR operation. This allows a validator to define rules
		 * like POST.a(required) OR GET.a(required) which will then cause the system
		 * to accept either.
		 */
		$binaryor->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			$result = $leafs[0]->resolve($scope);
			
			if (!$leafs[1]) { return $result; }
			
			foreach ($leafs[1][0] as $op) {
				$r = $op[1]->resolve($scope);
				$result = empty($r)? [] : array_merge($result, $r);
			}
			
			return $result;
		});
		
		/*
		 * The body of a binary AND operator. The application can then define
		 * requirements like POST.a(string) AND POST.b(number required)
		 */
		$binaryand->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			$result = $leafs[0]->resolve($scope);
			
			if (!$leafs[1]) { return $result; }
			
			foreach ($leafs[1][0] as $op) {
				$r = $op[1]->resolve($scope);
				$result = empty($r)? $result : array_merge($result, $r);
			}
			
			return $result;
		});
		
		/*
		 * An expression is either a single rule, or a statement wrapped in parentheses.
		 * This allows the system to build more complex rules like 
		 * POST a(required) AND (POST.b(string) OR POST.c(number))
		 */
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
		
		/*
		 * A rule is a combination of a variable identifier and a set of
		 * validation rules like POST.a(number required)
		 */
		$rule->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			$fn = $leafs[0]->resolve($scope);
			$args = $leafs[2]->resolve($scope);
			
			$payload = $scope->get($fn[0])[$fn[1]]?? null;
			$pass = [];
			
			foreach ($args as /*@var $arg ValidationRule*/$arg) {
				$tested = $arg->test($payload);
				$pass[] = $tested === false || $tested === true || $tested === null? null : $tested;
			}
			
			return array_filter($pass);
		});
		
		/*
		 * The function name is a bit misleading name for this, because it actually
		 * represents the item to be validated. Like POST.a
		 */
		$fnname->does(function ($leaf, $scope) {
			$leafs = $leaf->getLeafs();
			return [$leafs[0]->getBody(), $leafs[2]->getBody()];
		});
		
		/*
		 * The arguments are the actual functions to be applied to the argument.
		 * Since in a validator, the system will usually apply multiple functions 
		 * to a single value, it makes sense to invert it.
		 * 
		 * It would be rather nonsensical to write something like required(POST.a) AND number(POST.a)
		 * rather than POST.a(number required)
		 */
		$arguments->does(function ($leaf, $scope) {
			$_ret = collect($leaf->getLeafs()[0])->each(function ($e) use ($scope) {
				list($argument, $options) = $e;
				
				if (!isset($this->rules[$argument->getBody()])) { throw new PrivateException('Invalid validation rule'); }
				
				$params = $options? collect($options)->each(function ($e) use ($scope) { return $e->resolve($scope); })->toArray() : [[]];
				return call_user_func_array($this->rules[$argument->getBody()], $params[0]);
			})->toArray();
			
			return $_ret;
		});
		
		/*
		 * The arguments provide a series of modifiers to the functions. For  example
		 * POST.a(string length[5, 30]) allows you to define a validator that will
		 * test whether a is a string and between 5 and 30 characters long.
		 */
		$options->does(function ($leaf, $scope) {
			$raw = $leaf->getLeafs();
			
			$clean = [$raw[1]->getBody()];
			$modifiers = $raw[2];
			
			if (!empty($modifiers)) {
				foreach ($modifiers[0] as $modifier) {
					$clean[] = $modifier[1]->getBody();
				}
			}
			
			return $clean;
		});
		
		$res = $parser->parse($lex->tokenize($string));
		
		/*
		 * If the parser was unable to read anything from the expression, we rather
		 * fail and inform the user that the parsing went south.
		 */
		if (empty($res)) {
			throw new PrivateException('Parser could not read the expression', 2004102105);
		}
		
		return $res;
	}
	
	/**
	 * Sets a rule. If the rule already existed, it will be overwritten.
	 * 
	 * @param string $name
	 * @param \Closure $callable
	 */
	public function rule($name, $callable) {
		$this->rules[$name] = $callable;
	}
	
}
