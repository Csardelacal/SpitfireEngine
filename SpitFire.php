<?php namespace spitfire;

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

use spitfire\contracts\core\LocationsInterface;
use spitfire\core\Locations;
use spitfire\exceptions\ApplicationException;
use spitfire\provider\Container;

/**
 * Dispatcher class of Spitfire. Calls all the required classes for Spitfire to run.
 *
 * @author César de la Cal <cesar@magic3w.com>
 */
class SpitFire
{
	
	/**
	 *
	 * @var Container
	 */
	private $provider;
	
	/**
	 * Provides quick access to different locations that the system will commonly
	 * use. This object is read only.
	 *
	 * @var Locations
	 */
	private $locations;
	
	public function __construct()
	{
		
		$this->locations = new Locations(defined('BASEDIR')? rtrim(BASEDIR, '\/') : __DIR__);
		
		/*
		 * Initialize the service container, which will manage all the services that
		 * the framework provides to the application.
		 */
		$this->provider = new Container();
		$this->provider()->set(Locations::class, $this->locations);
		$this->provider()->set(LocationsInterface::class, $this->locations);
	}
	
	/**
	 * @throws ApplicationException
	 */
	public static function baseUrl() : string
	{
		/**
		 * If the application has a url defined as the base url for the application,
		 * we use that.
		 */
		if (config('app.url') !== null) {
			/**
			 * The app.url must obviously be a string.
			 */
			assert(is_string(config('app.url')));
			
			return config('app.url');
		}
		
		/**
		 * CLI applications must have a base url defined, since otherwise the application
		 * could be generating bad URLs without our knowledge. This is usually a very bad
		 * experience for the user who receives a URL they cannot access.
		 */
		if (cli()) {
			throw new ApplicationException('CLI applications require the app.url config to be defined', 2104191131);
		}
		
		assert(is_string($_SERVER['PHP_SELF']));
		
		/**
		 * Poorly configured applications can always fall back to guessing the base url.
		 * This is by no means a good way of handling this.
		 */
		$public = explode('/public/index.php', $_SERVER['PHP_SELF'], 2)[0];
		return dirname($public);
	}
	
	/**
	 *
	 * @return Locations
	 */
	public function locations()
	{
		return $this->locations;
	}
	
	/**
	 * Return the dependency container for this Spitfire instance. This container
	 * allows the application to inject behaviors into the
	 *
	 * @return Container
	 */
	public function provider()
	{
		return $this->provider;
	}
}
