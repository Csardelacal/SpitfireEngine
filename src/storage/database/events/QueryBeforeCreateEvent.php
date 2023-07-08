<?php namespace spitfire\storage\database\events;
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


use spitfire\event\Event;
use spitfire\storage\database\ConnectionInterface;
use spitfire\storage\database\Query;

class QueryBeforeCreateEvent extends Event
{
	
	/**
	 *
	 * @var ConnectionInterface
	 */
	private $driver;
	
	/**
	 *
	 * @var Query
	 */
	private $query;
	
	/**
	 *
	 * @var mixed[]
	 */
	private $options;
	
	/**
	 *
	 * @param ConnectionInterface $driver
	 * @param Query $query
	 * @param mixed[] $options
	 */
	public function __construct(
		ConnectionInterface $driver,
		Query $query,
		array $options = []
	) {
		$this->query = $query;
		$this->options = $options;
		$this->driver = $driver;
	}
	
	public function getQuery() : Query
	{
		return $this->query;
	}
	
	/**
	 *
	 * @return string[]
	 */
	public function getOptions() : array
	{
		return $this->options;
	}
	
	public function getConnection() : ConnectionInterface
	{
		return $this->driver;
	}
}
