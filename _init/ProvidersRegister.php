<?php namespace spitfire\_init;

use spitfire\core\service\Provider;

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
 * This init script allows our application to register all the service providers'
 * services that we need in order to make the component they provide work properly.
 * 
 * A service provider must be able to register components without any dependencies
 * on other components, if you need to depend on other components, please refer to
 * the init method.
 */
class ProvidersRegister implements InitScriptInterface
{
	
	public function exec(): void 
	{
		
		/*
		 * Instance all the service providers and call the register method, this
		 * allows them to bind all the services they provide.
		 */
		$providers = config('app.providers');
		
		array_walk_recursive($providers, function ($name) {
			/**
			 * @var Provider $provider
			 */
			$provider = spitfire()->provider()->get($name);
			$provider->register();
		});
	}
}
