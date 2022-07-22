<?php namespace spitfire\storage\database;

use spitfire\collection\Collection;
use spitfire\collection\OutOfBoundsException;

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
 * The Schema class allows a database driver to maintain a list of layouts
 * mapped together under a single "umbrella" that the system can use to refer
 * to the schema.
 *
 * This also allows the drivers to suggest migrations using a diff method in
 * future versions of the application.
 *
 */
class Schema
{
	
	/**
	 * The name of the schema. Most DBMS allow having multiple schemas in a single server
	 * or have a filename to identify the schema.
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The layouts / Tables that the schema is holding for the server.
	 *
	 * @var Collection<Layout>
	 */
	private $layouts;
	
	/**
	 * Instance a new schema.
	 *
	 * @param string $name
	 */
	public function __construct(string $name)
	{
		$this->name = $name;
		$this->layouts = new Collection();
	}
	
	/**
	 * Returns the name of the schema. Some DBMS do not support multiple schemas on a single
	 * database server / file. These may return an empty string.
	 *
	 * @return string
	 */
	public function getName() : string
	{
		return $this->name;
	}
	
	/**
	 * Gets a layout by it's name. Please note that when disabling assertions this code will no
	 * longer check whether the layout actually exists and may throw errors.
	 *
	 * @param string $name
	 * @throws OutOfBoundsException
	 * @return Layout
	 */
	public function getLayoutByName(string $name) : Layout
	{
		assert($this->layouts->has($name));
		return $this->layouts[$name];
	}
	
	/**
	 * Checks whether the schema contains a layout with a certain name. This is generally used for testing
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasLayoutByName(string $name) : bool
	{
		return $this->layouts->has($name);
	}
	
	/**
	 * Returns the list of layouts.
	 *
	 * @return Collection<Layout>
	 */
	public function getLayouts() : Collection
	{
		return $this->layouts;
	}
	
	/**
	 * Adds a new layout with the name you provided.
	 *
	 * @param string $name
	 * @return Layout
	 */
	public function newLayout(string $name) : Layout
	{
		$layout = new Layout($name);
		$this->layouts[$name] = $layout;
		return $layout;
	}
	
	/**
	 * Adds a Layout to the Schema.
	 *
	 * @param Layout $layout
	 * @return Schema
	 */
	public function putLayout(Layout $layout) : Schema
	{
		$this->layouts[$layout->getTableName()] = $layout;
		return $this;
	}
	
	/**
	 * Removes a Layout from the Schema.
	 *
	 * @param Layout $layout
	 * @return Schema
	 */
	public function removeLayout(Layout $layout) : Schema
	{
		unset($this->layouts[$layout->getTableName()]);
		return $this;
	}
}
