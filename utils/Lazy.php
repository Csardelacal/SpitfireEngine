<?php namespace spitfire\utils;

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

use BadMethodCallException;
use Closure;

trait Lazy
{
	
	private object $delegate;
	
	/**
	 *
	 * @param object|Closure $delegate
	 * @return void
	 */
	protected function delegate(object $delegate) : void
	{
		$this->delegate = $delegate;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param mixed[] $args
	 * @return mixed
	 */
	public function __call(string $name, array $args)
	{
		$delegate = $this->delegate;
		
		if ($delegate instanceof Closure) {
			$delegate = ($this->delegate->bindTo($this))();
		}
		
		assert(is_object($delegate));
		
		if (method_exists($delegate, $name)) {
			return $delegate->$name(...$args);
		}
		
		throw new BadMethodCallException(sprintf(
			'Type %s has no method %s',
			get_class($delegate),
			$name
		));
	}
}
