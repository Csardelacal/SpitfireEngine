<?php namespace spitfire\event;

use PHPUnit\Framework\TestCase;

/* 
 * The MIT License
 *
 * Copyright 2020 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class BasicEventNestedDispatchersTest extends TestCase
{
	
	public function testNestedDispatchers1() {
		
		$parent = new EventDispatch();
		$child = new EventDispatch($parent);
		
		$child->hook('m3w.test', new Listener(function (Event$e) {
			return 'hello ' . $e->payload();
		}));
		
		$parent->hook('m3w.test', new Listener(function (Event$e) {
			$e->stopPropagation();
			return 'bye ' . $e->payload();
		}));
		
		$result = $child->dispatch('m3w.test', new Event('world'), null);
		$this->assertEquals('bye world', $result);
	}
	
	public function testNestedDispatchers2() {
		
		$parent = new EventDispatch();
		$child = new EventDispatch($parent);
		
		$child->hook('m3w.test', new Listener(function (Event$e) {
			$e->stopPropagation();
			return 'hello ' . $e->payload();
		}));
		
		$parent->hook('m3w.test', new Listener(function (Event$e) {
			$e->stopPropagation();
			return 'bye ' . $e->payload();
		}));
		
		$result = $child->dispatch('m3w.test', new Event('world'), null);
		$this->assertEquals('hello world', $result);
	}
	
	public function testNestedDispatchersNoBubble() {
		
		$parent = new EventDispatch();
		$child = new EventDispatch($parent);
		
		$child->hook('m3w.test', new Listener(function (Event$e) {
			return 'hello ' . $e->payload();
		}));
		
		$parent->hook('m3w.test', new Listener(function (Event$e) {
			$e->stopPropagation();
			return 'bye ' . $e->payload();
		}));
		
		$result = $child->dispatch('m3w.test', new Event('world', ['bubbles' => false]), null);
		$this->assertEquals('hello world', $result);
	}
}
