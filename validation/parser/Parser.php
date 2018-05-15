<?php namespace spitfire\validation\parser;

use spitfire\exceptions\PrivateException;

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

abstract class Parser
{
	
	public function parse(Component$component, GroupComponent$parent) {
		$current = $parent;
		
		if ($component instanceof GroupComponent) { 
			$current = $current->append(new GroupComponent($current));
			$component->each(function($e) use (&$current) { $current = $this->parse($e, $current); }); 
			$current = $current->end();
		}
		if ($component instanceof LiteralComponent)  { $current->append($component); }
		if ($component instanceof UnparsedComponent) { $current = $this->extract($current, $component); }
		
		//if ($return !== $current) { throw new PrivateException('Parse error', 1805062126); }
		
		return $current;
	}
	
	abstract public function extract(GroupComponent$parent, UnparsedComponent$str);
}
