<?php namespace spitfire\validation\parser;

use spitfire\validation\parser\preprocessor\Preprocessor;
use spitfire\validation\rules\EmptyValidationRule;
use spitfire\validation\rules\FilterValidationRule;
use spitfire\validation\rules\LengthValidationRule;
use spitfire\validation\rules\NotValidationRule;
use spitfire\validation\rules\PositiveNumberValidationRule;
use spitfire\validation\rules\TypeNumberValidationRule;
use spitfire\validation\rules\TypeStringValidationRule;

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
 * 
 * @todo Consider a more elaborate mechanism for rule creation. Generally speaking,
 * the creation of rules should be rather simple. Since this element only interfaces
 * with the actual rules. But maybe it could be interesting.
 */
class Parser
{
	private $preprocessor;
	
	private $logic = [];
	
	private $rules = [];
	
	public function __construct() {
		
		$this->preprocessor = new Preprocessor();
		$this->logic[] = new LogicProcessor('OR');
		$this->logic[] = new LogicProcessor('AND');
		
		#Create the default rules
		$this->rules['string'] = function() { return new TypeStringValidationRule('Accepts only strings'); };
		$this->rules['email']  = function() { return new FilterValidationRule(FILTER_VALIDATE_EMAIL, 'Invalid email provided'); };
		$this->rules['length'] = function($min, $max = null) { return new LengthValidationRule($min, $max, sprintf('Field length must be between %s and %s characters', $min, $max)); };
		$this->rules['not']    = function($value) { return new NotValidationRule($value, sprintf('Value "%s" is not allowed', $value)); };
		$this->rules['positive']=function() { return new PositiveNumberValidationRule('Value must be a positive number'); };
		$this->rules['number'] = function() { return new TypeNumberValidationRule('Value must be a number'); };
		$this->rules['required']=function() { return new EmptyValidationRule('Value is required. Cannot be empty'); };
	}
	
	public function parse($string) {
		$result = $this->preprocessor->prepare($string)->tokenize();
		
		foreach ($this->logic as $l) {
			$l->run($result);
		}
		
		return $result->make($this);
	}
	
	public function rule($name, $callable) {
		$this->rules[$name] = $callable;
	}
	
	public function makeRules($from) {
		
		for($i = 0; $i < count($from); $i++) {

			$rule = $from[$i]->getContent();

			if (isset($from[$i + 1]) && $from[$i+1] instanceof Options) {
				$options = $from[$i+1];
				$i++;
			}
			else {
				$options = null;
			}
			
			
			if (!isset($this->rules[$rule])) { throw new PrivateException('Invalid rule: ' . $rule, 1805171527); }
			
			$_ret[] = call_user_func_array($this->rules[$rule], $options? $options->getItems() : []);
		}

		return array_filter($_ret);
	}
}
