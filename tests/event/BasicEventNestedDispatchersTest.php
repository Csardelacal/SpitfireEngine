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

class BasicEventNestedDispatchersTest extends TestCase
{
	
	public function testNestedDispatchers1() {
		
		$parent = new EventTarget();
		$child = new EventTarget($parent);
		
		$child->hook(TestEvent::class, new Listener(function (TestEvent $e) {
			$e->setPayload('hello ' . $e->payload());
		}));
		
		$parent->hook(TestEvent::class, new Listener(function (TestEvent $e) {
			$e->stopPropagation();
			$e->setPayload('bye ' . $e->payload());
		}));
		
		$result = $child->dispatch(new TestEvent('world'), null);
		$this->assertEquals('bye hello world', $result->payload());
	}
	
	public function testNestedDispatchers2() {
		
		$parent = new EventTarget();
		$child = new EventTarget($parent);
		
		$child->hook(TestEvent::class, new Listener(function (TestEvent $e) {
			$e->stopPropagation();
			$e->setPayload('hello ' . $e->payload());
		}));
		
		$parent->hook(TestEvent::class, new Listener(function (TestEvent $e) {
			$e->stopPropagation();
			$e->setPayload('bye ' . $e->payload());
		}));
		
		$result = $child->dispatch(new TestEvent('world'), null);
		$this->assertEquals('hello world', $result->payload());
	}
	
	public function testNestedDispatchersNoBubble() {
		
		$parent = new EventTarget();
		$child = new EventTarget($parent);
		
		$child->hook(TestEvent::class, new Listener(function (TestEvent $e) {
			$e->setPayload('hello ' . $e->payload());
		}));
		
		$parent->hook(TestEvent::class, new Listener(function (TestEvent $e) {
			$e->stopPropagation();
			$e->setPayload('bye ' . $e->payload());
		}));
		
		$result = $child->dispatch((new TestEvent('world'))->preventBubbling(), null);
		$this->assertEquals('hello world', $result->payload());
	}
}
