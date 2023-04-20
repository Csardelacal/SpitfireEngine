<?php namespace spitfire\storage\database;

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

use Psr\Container\ContainerInterface;
use spitfire\collection\Collection;
use spitfire\collection\TypedCollection;
use spitfire\contracts\ConfigurationInterface;
use spitfire\provider\Container;
use spitfire\storage\database\drivers\Adapter;
use spitfire\storage\database\drivers\mysqlpdo\Driver;

class ConnectionManager
{
	
	/**
	 *
	 * @var Collection<Connection>
	 */
	private $connections;
	
	/**
	 *
	 * @var ContainerInterface
	 */
	private $container;
	
	/**
	 * 
	 * @var string
	 */
	private $schemaFile;
	
	/**
	 *
	 * @var ConfigurationInterface
	 */
	private ConfigurationInterface $definitions;
	
	/**
	 *
	 * @param ConfigurationInterface $definitions
	 */
	public function __construct(ContainerInterface $container, ConfigurationInterface $definitions, string $schemaFile)
	{
		$this->container = $container;
		$this->definitions = $definitions;
		$this->schemaFile = $schemaFile;
		$this->connections = new TypedCollection(Connection::class);
	}
	
	public function get(string $name) : Connection
	{
		
		/**
		 * If the key is available, we return it from our cache
		 */
		if ($this->connections->has($name)) {
			return $this->connections->$name;
		}
		
		$connection = $this->make($name);
		$this->connections->offsetSet($name, $connection);
		return $connection;
	}
	
	
	public function make(string $name) : Connection
	{
		
		
		assert($this->definitions !== null);
		assert(array_search($name, $this->definitions->keys()) !== false);
		
		/**
		 * Load the definition from the configuration excerpt we received from
		 * the framework.
		 */
		$definition = $this->definitions->splice($name);
		
		/**
		 * We assemble a settings object tat can be used to initialize the driver. The
		 * data will depend on the database driver.
		 */
		$settings = $definition->get('settings')? 
			Settings::fromURL($definition->get('settings')) :
			scope($definition->splice('settings'), fn($c) => Settings::fromArray(array_filter([
				'driver' => $c->get('driver'),
				'server' => $c->get('server'),
				'port' => intval($c->get('port')),
				'user' => $c->get('user'),
				'password' => $c->get('password'),
				'schema' => $c->get('schema'),
				'prefix' => $c->get('prefix'),
				'encoding' => $c->get('encoding')
			])));
		
		/**
		 * Find the schema cache file. This contains information about the state of the schema,
		 * allowing the application to verify that no data is being accessed that should not be
		 * read.
		 */
		$schemaFile = $definition->get('schema', $this->schemaFile);
		$schema = file_exists($schemaFile)? include($schemaFile) : new Schema($settings->getSchema());
		
		/**
		 * Initialize the driver, find the appropriate driver class, and instance it with the settings
		 * we found earlier.
		 *
		 * To ensure proper state, we verify the object we received is actually an instance of a Driver.
		 */
		$type   = $definition->get('driver');
		
		$driver = $this->container->get(Container::class)->assemble($type, [
			'settings' => $settings
		]);
		
		assert($driver instanceof Driver);
		
		$driver->connect();
		
		/**
		 * Create the connection, cache it, and return it. Please note that this does not yet guarantee
		 * that the driver is working properly, drivers may be lazy with their connection to prevent
		 * starting connections for applications that may not need them.
		 */
		$connection = new Connection($schema, new Adapter($driver));
		
		return $connection;
	}
}
