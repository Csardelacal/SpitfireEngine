<?php namespace spitfire\core\config;

use spitfire\contracts\ConfigurationInterface;
use spitfire\exceptions\ApplicationException;
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

/**
 * Configuration contains an array of data parsed from a configuration file (or
 * multiple, in the event of the configuration referring to a directory that contains
 * configuration files).
 *
 * Due to the fact that configuration is cached, the system generates configuration
 * by invoking a static method that will recursively walk over all the files and
 * import the data, assembling a tree of arrays.
 *
 * When caching the configuration, the loaded environments are also cached and
 * therefore your application's cache will need to be rebuilt in order to load
 * new environments.
 *
 * Configuration files are automatically flattened, so that information can be
 * read with dot notation easily.
 *
 * NOTE: Configuration does not support arrays (this is why they are flattened). I seem
 * to get tripped up by this concept myself a lot, and this is why I'm adding this
 * note. If you need to configure something in an array style fashion you're probably
 * better off using service providers.
 */
class Configuration implements ConfigurationInterface
{
	
	/**
	 * Contains the configuration array. This array contains the flattened config
	 * from the configuration files (even if these contain arrays to set up the stuff)
	 *
	 * @var string[]
	 */
	private $data;
	
	/**
	 * Using a dot notation accessor for this class removes the complexity from this
	 * class and allows us to work on caching the data here.
	 *
	 * @var DotNotationAccessor
	 */
	private $interface;
	
	public function __construct($data = [])
	{
		$this->data = $data;
		$this->interface = new DotNotationAccessor($this->data);
	}
	
	/**
	 * Retrieve a configuration from the repository. You may retrieve a config
	 * as an array.
	 *
	 * @param string $key
	 * @param mixed $fallback
	 * @return string|int|bool|float|null
	 */
	public function get(string $key, $fallback = null) : string|int|bool|float|null
	{
		return $this->interface->has($key)? $this->interface->get($key) : $fallback;
	}
	
	/**
	 * Returns true if the key exists within the configuration.
	 */
	public function has(string $key) : bool
	{
		return $this->interface->has($key);
	}
	
	public function getAll(string $key): array
	{
		$raw = $this->interface->get($key, DotNotationAccessor::ALLOW_ARRAY_RETURN)?: [];
		$_ret = [];
		
		array_walk_recursive($raw, function (string $element) use(&$_ret) : void {
			$_ret[] = $element;
		});
		
		return $_ret;
	}
	
	/**
	 * Get a configuration object for the given subtree. This means that the 
	 * 
	 * @throws ApplicationException
	 * @param string $key
	 * @return ConfigurationInterface
	 */
	public function splice(string $key) : ConfigurationInterface
	{
		/**
		 * If the underlying array does not hold the configuration we're looking for,
		 * the application should not continue.
		 */
		assume($this->interface->has($key), sprintf('Configuration does not contain key %s', $key));
		
		/**
		 * Otherwise just return the data the application was actually requesting.
		 */
		return new self($this->interface->get($key, DotNotationAccessor::ALLOW_ARRAY_RETURN));
	}
	
	/**
	 * Returns the top level keys of the configuration. So a config with the keys 'a.b',
	 * 'a.c' and 'b.a' would return ['a', 'b']
	 * 
	 * @return string[]
	 */
	public function keys() : array
	{
		return array_keys($this->data);
	}
	
	/**
	 * Set a configuration. Please note that all the code that went before will not have
	 * received the configuration. Also, you should consider replacing calls to this with
	 * writing to the configuration directly whenever possible.
	 *
	 * @param string $key
	 * @param mixed $value
	 * @return Configuration
	 */
	public function set(string $key, $value = null)
	{
		$this->interface->set($key, $value);
		return $this;
	}
	
	/**
	 * Import the configuration from a file. These files can contain executable code, or
	 * environment calls, but whenever caching is enabled the configuration will be computed
	 * once and not be regenerated until you request it.
	 *
	 * @param string $namespace
	 * @param mixed[] $values
	 */
	public function import(string $namespace, $values)
	{
		$this->interface->set($namespace, $values);
		return $this;
	}
	
	/**
	 * Retrieve a configuration from the repository. You may not retrieve a config
	 * as an array.
	 *
	 * @return mixed[]
	 */
	public function export()
	{
		return $this->data;
	}
}
