<?php namespace spitfire\validation\parser;

use spitfire\validation\rules\EmptyValidationRule;
use spitfire\validation\rules\FilterValidationRule;
use spitfire\validation\rules\PositiveNumberValidationRule;

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

class GroupComponent extends Component
{
	
	private $items = [];
	
	public function __construct($items) {
		$this->items = $items;
	}
	
	public function tokenize() {
		$set = [];
		
		foreach ($this->items as $e) {
			if ($e instanceof GroupComponent) { $e->tokenize(); $set[] = $e; }
			elseif (is_string($e)) { $set = array_merge($set, array_map(function ($e) { return new Token($e); }, array_filter(explode(' ', $e)))); }
			else { $set[] = $e; }
		}
		
		$this->items = $set;
	}
	
	public function getItems() {
		return $this->items;
	}
	
	public function setItems($items) {
		$this->items = $items;
		return $this;
	}
	
	public function push($item) {
		$this->items[] = $item;
	}
	
	public function make() {
		$items = array_values($this->items);
		$_ret  = [];
		
		if (count($items) === 1) {
			return $items[0]->make();
		}
		elseif (count($items) === 2 && $items[0] instanceof Token && $items[1] instanceof GroupComponent) {
			$options = $items[1]->make();
			if (!is_array($options)) { $options = [$this->findRule($options, [])]; }
			return new postprocessor\FunctionPostProcessor($items[0]->make(), $options);
		}
		else {
			//The group represents parameters
			for($i = 0; $i < count($items); $i++) {
				
				$rule = $items[$i];
				
				if ($items[$i+1] instanceof OptionsComponent) {
					$options = $items[$i+1];
					$i++;
				}
				else {
					$options = [];
				}
				
				$_ret[] = $this->findRule($rule, $options);
			}

			return array_filter($_ret);
		}
	}
	
	public function findRule($name, $options) {
		
		switch($name) {
			case 'email'   : return new FilterValidationRule(FILTER_VALIDATE_EMAIL, 'Invalid email provided');
			case 'url'     : return new FilterValidationRule(FILTER_VALIDATE_URL, 'Invalid URL provided');
			case 'required': return new EmptyValidationRule('Field may not be empty');
			case 'positive': return new PositiveNumberValidationRule('Positive number required');
		}
		
		return null;
	}
		
	public function __toString() {
		return sprintf('group(%s)', implode(',', $this->items));
	}

}
