<?php namespace tests\spitfire\core\config;

use PHPUnit\Framework\TestCase;
use spitfire\core\config\Configuration;
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


class ConfigurationTest extends TestCase
{
	
	public function testBasicWrite() 
	{
		$config = new Configuration();
		$config->set('test', 'hello');
		$config->set('test.this', 'hello world');
		$config->set('test.that', false);
		$config->set('test.everything', null);
		
		$this->assertArrayHasKey('this', $config->get('test'));
		
		$this->assertEquals('hello world', $config->get('test.this'));
		$this->assertEquals(null, $config->get('test.everything'));
		$this->assertEquals(false, $config->get('test.that'));
		
		/**
		 * This key does not exist, so we would return the fallback value
		 */
		$this->assertEquals(null, $config->get('test.th'));
	}
	
	public function testImport() 
	{
		$config = new Configuration();
		$config->import('hello', [
			'world' => 'hello world',
			'everyone' => 'hello everyone'
		]);
		
		
		$this->assertArrayHasKey('world', $config->get('hello'));
		$this->assertEquals('hello world', $config->get('hello.world'));
	}
}
