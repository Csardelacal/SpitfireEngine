<?php namespace spitfire\_init;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use spitfire\contracts\core\kernel\InitScriptInterface;
use spitfire\core\Request;
use spitfire\provider\Container;

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
 * The init request script invokes the Request::fromGlobals to create
 * a request object that is then injected into the global state of the
 * application.
 */
class InitRequest implements InitScriptInterface
{
	
	public function exec(ContainerInterface $container) : void
	{
		assert($container instanceof Container);
		
		$request = Request::fromGlobals();
		
		/**
		 * Register the request for both the Spitfire Request class and the
		 * RequestInterface that we received from the PSR package.
		 */
		$container->set(ServerRequestInterface::class, $request);
		$container->set(Request::class, $request);
	}
}
