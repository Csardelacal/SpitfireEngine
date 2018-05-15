<?php namespace spitfire\validation\parser\logicparser;

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

abstract class LogicProcessor
{
	
	public function exists(GroupComponent$component) {
		foreach ($component->getItems() as $item) {
			if ($item instanceof Token && $item->getContent() == $this->token()) {
				return true;
			}
		}
		
		return false;
	}
	
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
		$copy  = $this->make();
		$child = new GroupComponent([]);
		$copy->push($child);
		
		
		foreach ($items as $item) {
			echo 'Testing ', $item, PHP_EOL;
			if ($item instanceof Token && $item->getContent() == $this->token()) {
				echo 'Entering...', PHP_EOL;
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
	
	abstract public function token();
	abstract public function make($items = []);

}