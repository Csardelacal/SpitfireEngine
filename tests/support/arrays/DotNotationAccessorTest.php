<?php namespace tests\spitfire\support\arrays;

use PHPUnit\Framework\TestCase;
use spitfire\support\arrays\DotNotationAccessor;

/* 
 * Copyright (C) 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * MA 02110-1301  USA
 */

class DotNotationAccessorTest extends TestCase
{
	
	public function testRead() 
	{
		$array = [
			'a' => 'b',
			'foo' => [
				'bar' => 'baz'
			],
			2 => [3]
		];
		
		$interface = new DotNotationAccessor($array);
		
		$this->assertEquals('b', $interface->get('a'));
		$this->assertEquals('baz', $interface->get('foo.bar'));
	}
	
	/**
	 * Test that changes made to the array are listened to by the accessor.
	 */
	public function testExternalChanges() 
	{
		$array = [
			'a' => 'b',
			'foo' => [
				'bar' => 'baz'
			],
			2 => [3]
		];
		
		$interface = new DotNotationAccessor($array);
		
		$this->assertEquals('b', $interface->get('a'));
		$this->assertEquals('baz', $interface->get('foo.bar'));
		
		$array['foo']['bar'] = 'Hello world';
		$this->assertEquals('Hello world', $interface->get('foo.bar'));
		
		$array['foo'] = ['bar' => 'Bye world'];
		$this->assertEquals('Bye world', $interface->get('foo.bar'));
	}
	
	/**
	 * Test that changes that were made inside the accessor class actually are
	 * broadcast to the array it's monitoring
	 */
	public function testInternalChanges() 
	{
		$array = [
			'a' => 'b',
			'foo' => [
				'bar' => 'baz'
			],
			2 => [3]
		];
		
		$interface = new DotNotationAccessor($array);
		
		$this->assertEquals('b', $interface->get('a'));
		$this->assertEquals('baz', $interface->get('foo.bar'));
		
		$interface->set('foo.bar', 'Hello world');
		$this->assertEquals('Hello world', $array['foo']['bar']);
		
		$interface->set('foo', ['bar' => 'Bye world']);
		$this->assertEquals('Bye world', $array['foo']['bar']);
	}
	
	public function testFlags()
	{
		$array = [
			'a' => 'b',
			'foo' => [
				'bar' => 'baz'
			],
			2 => [3]
		];
		
		$interface = new DotNotationAccessor($array);
		$this->assertEquals(null, $interface->get('foo'));
		$this->assertArrayHasKey('bar', $interface->get('foo', DotNotationAccessor::ALLOW_ARRAY_RETURN));
	}
}
