<?php namespace spitfire\mvc\providers;

use spitfire\core\app\support\directors\ManifestCacheBuildDirector;
use spitfire\storage\support\directors\CheckStoragePermissionsDirector;
use spitfire\service\Provider;
use spitfire\core\kernel\ConsoleKernel;

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
 * The director provider class registers the commands that spitfire provides to
 * applications using it.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class DirectorProvider extends Provider
{
	
	/**
	 * 
	 */
	public function register()
	{
		/*
		 * The director provider is only loaded in order to register the known 
		 * spitfire provided services.
		 */
	}
	
	
	public function init()
	{
		
		$kernel = spitfire()->kernel();
		
		/*
		 * We only need to register the directors if our kernel is actually the 
		 * console kernel. We cannot work with directors on the web server.
		 */
		if ($kernel instanceof ConsoleKernel) {
			$kernel->register('spitfire.config.build', new \spitfire\config\directors\BuildConfigDirector());
			$kernel->register('spitfire.app.cache.build', new ManifestCacheBuildDirector);
			$kernel->register('spitfire.defer.process', \spitfire\defer\directors\ProcessDirector::class);
			$kernel->register('spitfire.storage.check.permissions', CheckStoragePermissionsDirector::class);
		}
	}
}
