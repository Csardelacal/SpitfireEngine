<?php namespace tests\spitfire\core;
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


use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use spitfire\core\Headers;

class HeadersTest extends TestCase
{
	
	public function testContentType()
	{
		
		$t = new Headers();
		
		$t->contentType('php', 'utf-8');
		$this->assertEquals('text/html;charset=utf-8', $t->get('Content-type')[0]);
		
		$t->contentType('html', 'utf-8');
		$this->assertEquals('text/html;charset=utf-8', $t->get('Content-type')[0]);
		
		$t->contentType('json', 'utf-8');
		$this->assertEquals('application/json;charset=utf-8', $t->get('Content-type')[0]);
		
		$t->contentType('xml', 'utf-8');
		$this->assertEquals('application/xml;charset=utf-8', $t->get('Content-type')[0]);
	}
	
	public function testCrazyCase()
	{
		
		$t = new Headers();
		
		$t->replace('ConTENT-TypE', 'application/json;charset=utf-8');
		$this->assertEquals('application/json;charset=utf-8', $t->get('content-type')[0]);
	}
	
	/**
	 */
	public function testInvalidStatus()
	{
		$t = new Headers();
		
		$this->expectException(InvalidArgumentException::class);
		$t->status('22');
	}
}
