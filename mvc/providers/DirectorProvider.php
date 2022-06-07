<?php namespace spitfire\mvc\providers;

use Psr\Container\ContainerInterface;
use spitfire\core\kernel\ConsoleKernel;
use spitfire\core\kernel\KernelInterface;
use spitfire\core\service\Provider as ServiceProvider;

use spitfire\core\app\support\directors\ManifestCacheBuildDirector;
use spitfire\core\config\Configuration;
use spitfire\core\config\directors\BuildConfigurationDirector;
use spitfire\core\Locations;
use spitfire\storage\support\directors\CheckStoragePermissionsDirector;

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
class DirectorProvider extends ServiceProvider
{
	
	/**
	 * 
	 */
	public function register(ContainerInterface $container)
	{
		/*
		 * The director provider is only loaded in order to register the known 
		 * spitfire provided services.
		 */
	}
	
	
	public function init(ContainerInterface $container)
	{
		
		$kernel = $container->get(ConsoleKernel::class);
		
		/*
		 * We only need to register the directors if our kernel is actually the 
		 * console kernel. We cannot work with directors on the web server.
		 */
		$locations = $container->get(Locations::class);
		
		$kernel->register(new BuildConfigurationDirector(
			$locations->root('bin/config.php'), 
			$container->get(Configuration::class))
		);
		
		$kernel->register(new CheckStoragePermissionsDirector($locations));
	}
}
