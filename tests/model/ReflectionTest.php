<?php  namespace tests\spitfire\model;

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
use spitfire\model\attribute\Integer;
use spitfire\model\ReflectionModel;
use tests\spitfire\model\fixtures\TestModel;

class ReflectionTest extends TestCase
{
	
	public function testFields()
	{
		$reflection = new ReflectionModel(TestModel::class);
		$fields = $reflection->getFields();
		
		$this->assertEquals(5, $fields->count());
		
		$test = $fields['test'];
		$this->assertEquals(true, $test->getNullable());
		$this->assertEquals('test', $test->getName());
		$this->assertInstanceOf(Integer::class, $test->getType());
	}
}
