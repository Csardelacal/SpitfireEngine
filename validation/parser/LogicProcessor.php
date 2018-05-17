<?php namespace spitfire\validation\parser;

use spitfire\validation\parser\GroupComponent;
use spitfire\validation\parser\Token;

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
 * Parsing Spitfire's validation language is not a simple feat. While simple, the
 * language has some caveats that cause it to be rather hard to interpret.
 * 
 * At this stage, the statement the user provided has been broken up into several
 * layers (groups, and options - depending on whether they were delimited by 
 * parenthesis or brackets) and contain a series of tokens.
 * 
 * The logic processor will walk the groups looking for suitable tokens (at this
 * point it's just AND and OR) and break the group up if it contains a token,
 * wrapping the pieces up in several sub-groups.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class LogicProcessor
{
	
	private $type;
	
	public function __construct($type) {
		$this->type = $type;
	}
	
	/**
	 * This method checks if the appropriate token exists within the group. While
	 * this usually amounts to double checking the group in an unnecessary fashion,
	 * it usually prevents the group from instancing several objects and discarding 
	 * them if they were unneeded.
	 * 
	 * @param GroupComponent $component
	 * @return boolean
	 */
	public function exists(GroupComponent$component) {
		foreach ($component->getItems() as $item) {
			if ($item instanceof Token && $item->getContent() == $this->type) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * This is the main method of a logic processor. These components are in 
	 * charge of replacing logical operator tokens (AND and OR) with components
	 * that represent them appropriately.
	 * 
	 * @param GroupComponent $component
	 */
	public function run(GroupComponent$component) {
		if (!$this->exists($component)) { 
			foreach ($component->getItems() as $item) { 
				if ($item instanceof GroupComponent) { 
					$this->run($item); 
				}
			}
			return;
		}
		
		$items = $component->getItems();
		$copy  = new GroupComponent([], $this->type);
		$child = new GroupComponent([]);
		$copy->push($child);
		
		
		foreach ($items as $item) {
			if ($item instanceof Token && $item->getContent() == $this->type) {
				$child = new GroupComponent([]);
				$copy->push($child);
			}
			elseif ($item instanceof GroupComponent) {
				$this->run($item);
				$child->push($item);
			}
			else {
				$child->push($item);
			}
		}
		
		$component->setItems([$copy]);
	}

}