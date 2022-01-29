<?php namespace spitfire\_init;

use spitfire\core\app\support\manifest\ComposerReader;
use spitfire\core\config\Configuration;

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

class ProvidersFromManifest implements InitScriptInterface
{
	
	public function exec(): void 
	{
		
		$cache = spitfire()->locations()->root('bin/providers.php');
		
		if (file_exists($cache)) {
			$providers = include $cache;
		}
		
		else {
			$providers = ComposerReader::providers(spitfire()->locations()->root('vendor/composer/installed.json'));
		}
		
		/**
		 * Write the providers back to the configuration
		 */
		$config = spitfire()->provider()->get(Configuration::class);
		assert($config instanceof Configuration);
		
		$config->set('app.providers._composer', $providers);
	}
}
