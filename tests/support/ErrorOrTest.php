<?php namespace tests\spitfire\support\arrays;

/*
 *
 * Copyright (C) 2021-2023 César de la Cal Bretschneider <cesar@magic3w.com>.
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
use spitfire\collection\Collection;
use spitfire\support\ErrorOr;


class ErrorOrTest extends TestCase
{
	public function testBasicFailure()
	{
	
		$result = ErrorOr::fail('Did not work', 1);
		$this->assertFalse($result->success());
	}
	
	public function testBasicSuccess()
	{
		$result = ErrorOr::succeed(new Collection());
		$this->assertTrue($result->success());
		$this->assertInstanceOf(Collection::class, $result->releaseValue());
	}
}
