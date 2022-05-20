<?php namespace spitfire\storage\database\tests\drivers\mysql;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use spitfire\storage\database\DriverInterface;
use spitfire\storage\database\drivers\mysqlpdo\Driver;
use spitfire\storage\database\drivers\mysqlpdo\NoopDriver;
use spitfire\storage\database\Settings;

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

/**
 *
 */
class NoopDriverTest extends TestCase
{
	
	/**
	 *
	 * @var TestHandler
	 */
	private $handler;
	
	/**
	 *
	 * @var Logger
	 */
	private $logger;
	
	/**
	 *
	 * @var NoopDriver
	 */
	private $driver;
	
	public function setUp() : void
	{
		$this->handler = new TestHandler();
		$this->logger = new Logger('test', [$this->handler]);
		$this->driver = new Driver(Settings::fromArray([]), $this->logger);
		$this->driver->mode(DriverInterface::MODE_LOG);
	}
	
	public function testExec()
	{
		$this->driver->create();
		$records = $this->handler->getRecords();
		
		$this->assertCount(2, $records);
		$this->assertEquals('CREATE DATABASE `database`', $records[0]['message']);
	}
}
