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


use PHPUnit\Framework\TestCase;
use tests\spitfire\event\TestEvent;
use tests\spitfire\event\TestEvent2;

class NestedEventTest extends TestCase
{
	
	
	public function testNestedEventMultipleListeners() {
		
		$dispatcher = new EventDispatch();
		
		$dispatcher->hook(TestEvent::class, new Listener(function (TestEvent $e) use ($dispatcher) { 
			$e->preventDefault();
			return 'hello ' . $dispatcher->dispatch(new TestEvent2($e->payload()), function (TestEvent2 $e) {
				return $e->payload() . 'a';
			}); 
		}));
		
		$dispatcher->hook(TestEvent2::class, new Listener(function (TestEvent2 $e) { 
			$e->preventDefault();
			return $e->payload() . 's'; 
		}));
		
		$result = $dispatcher->dispatch(new TestEvent('world'), function (TestEvent $e) {
			return 'bye ' . $e->payload();
		});
		
		$this->assertEquals($result, 'hello worlds');
	}
	
}
