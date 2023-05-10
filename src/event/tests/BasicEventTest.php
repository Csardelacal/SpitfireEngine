<?php namespace spitfire\event;

use spitfire\event\tests\TestEvent;

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

/**
 * 
 */
class BasicEventTest extends \PHPUnit\Framework\TestCase
{
	
	public function testBasicEvent1() {
		
		$dispatcher = new EventDispatch();
		
		$dispatcher->hook(TestEvent::class, new Listener(function (TestEvent $e) { 
			$e->preventDefault();
			return $e->payload() . ' world'; 
		}));
		
		$result = $dispatcher->dispatch(new TestEvent('hello'), function (TestEvent $e) {
			return $e->payload() . ' death';
		});
		
		$this->assertEquals($result, 'hello world');
	}
	
	public function testBasicEventNoOverride() {
		
		$dispatcher = new EventDispatch();
		
		$dispatcher->hook(TestEvent::class, new Listener(function (TestEvent $e) { 
			return 'hello ' . $e->payload(); 
		}));
		
		$result = $dispatcher->dispatch(new TestEvent('world'), function (TestEvent $e) {
			return 'bye ' . $e->payload();
		});
		
		$this->assertEquals($result, 'bye world');
	}
	
	/**
	 * The behavior can be a bit confusing whenever multiple listeners are registered
	 * for the same behavior. You should therefore avoid using too many listeners
	 * to modify your application.
	 */
	public function testBasicEventMultipleListeners() {
		
		$dispatcher = new EventDispatch();
		
		$dispatcher->hook(TestEvent::class, new Listener(function (TestEvent $e) { 
			$e->preventDefault();
			return 'hello ' . $e->payload(); 
		}));
		
		/*
		 * This listener overrides the output from the first, but does not invoke
		 * preventdefault anew.
		 */
		$dispatcher->hook(TestEvent::class, new Listener(function (TestEvent $e) { 
			return 'stop ' . $e->payload(); 
		}));
		
		$result = $dispatcher->dispatch(new TestEvent('world'), function (TestEvent $e) {
			return 'bye ' . $e->payload();
		});
		
		$this->assertEquals($result, 'stop world');
	}
}
