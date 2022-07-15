<?php namespace spitfire\_init;

use spitfire\contracts\ConfigurationInterface;
use spitfire\core\config\Configuration;
use spitfire\core\config\ConfigurationLoader;
use spitfire\core\Environment;

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

class LoadConfiguration implements InitScriptInterface
{
	
	public function exec(): void
	{
		
		/**
		 * If the cache file is available, we can use it to bootstrap the application.
		 */
		if (file_exists(spitfire()->locations()->root('bin/config.php'))) {
			spitfire()->provider()->set(Configuration::class, new Configuration(include spitfire()->locations()->root('bin/config.php')));
			return;
		}
		
		spitfire()->provider()->set(Environment::class, new Environment(spitfire()->locations()->root('.env')));
		
		$loader = new ConfigurationLoader(spitfire()->locations());
		$config = $loader->make();
		
		spitfire()->provider()->set(Configuration::class, $config);
		spitfire()->provider()->set(ConfigurationInterface::class, $config);
	}
}
