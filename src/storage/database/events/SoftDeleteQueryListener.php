<?php namespace spitfire\storage\database\events;

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

use BadMethodCallException;
use spitfire\event\Event;
use spitfire\event\ListenerInterface;


 /**
  *
  * @implements ListenerInterface<QueryBeforeCreateEvent>
  */
class SoftDeleteQueryListener implements ListenerInterface
{
	
	/**
	 *
	 * @var string
	 */
	private $field;
	
	public function __construct(string $field)
	{
		$this->field = $field;
		echo 'Constructor', PHP_EOL;
	}
	
	public function react(Event $event)
	{
		echo 'React', PHP_EOL;
		/**
		 * If the payload is not good, we cannot proceed.
		 */
		assert($event instanceof QueryBeforeCreateEvent);
		
		$field = $event->getQuery()->getTable()->getOutput($this->field);
		assert($field !== null);
		
		$options = $event->getOptions();
		
		/**
		 * The default behavior is to just exclude trashed items. This means we need
		 * to add a restriction to exclude sof deleted items.
		 */
		if (!isset($options['trashed']) || $options['trashed'] === 'exclude') {
			$event->getQuery()->getRestrictions()->where($field, null);
			return;
		}
		
		/**
		 * If we received an include value for trashed, we will assume that the developer
		 * wishes to include all the results.
		 */
		if ($options['trashed'] === 'include') {
			return;
		}
		
		/**
		 * When we ask for only deleted values, we are requesting the application to omit
		 * all the non deleted values and to return only deleted
		 */
		if ($options['trashed'] === 'only') {
			$event->getQuery()->getRestrictions()->where($field, '!=', null);
			return;
		}
		
		throw new BadMethodCallException('Invalid argument: trashed', 2307081555);
		
	}
}
