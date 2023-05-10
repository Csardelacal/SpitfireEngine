<?php namespace spitfire\event;

/*
 *
 * Copyright (C) 2023-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-13 01  USA
 *
 */


use tests\spitfire\event\TestEvent;


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
