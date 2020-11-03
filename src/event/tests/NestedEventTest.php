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

class NestedEventTest extends TestCase
{
	
	
	public function testNestedEventMultipleListeners() {
		
		$dispatcher = new EventDispatch();
		
		$dispatcher->hook('m3w.test', new Listener(function (Event$e) use ($dispatcher) { 
			$e->preventDefault();
			return 'hello ' . $dispatcher->dispatch('m3w.test2', new Event($e->payload()), function (Event$e) {
				return $e->getPayload() . 'a';
			}); 
		}));
		
		$dispatcher->hook('m3w.test2', new Listener(function (Event$e) { 
			$e->preventDefault();
			return $e->payload() . 's'; 
		}));
		
		$result = $dispatcher->dispatch('m3w.test', new Event('world'), function (Event$e) {
			return 'bye ' . $e->payload();
		});
		
		$this->assertEquals($result, 'hello worlds');
	}
	
}
