<?php namespace spitfire\mvc\middleware\standard;

use spitfire\core\ContextInterface;
use spitfire\core\Environment;
use spitfire\core\Response;
use spitfire\mvc\middleware\exceptions\MaintenanceModeException;
use spitfire\mvc\middleware\MiddlewareInterface;

/* 
 * The MIT License
 *
 * Copyright 2020 César de la Cal Bretschneider <cesar@magic3w.com>.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * The maintenance middleware allows Spitfire to provide a maintenance mode, if 
 * the administrator sets the Environment variable `spitfire.maintenance` to true,
 * the maintenance mode will fire an exception that will prevent execution from
 * continuing and try to fetch a user error page for the maintenance mode.
 * 
 * The Environment `spitfire.maintenance` can contain an integer that will be passed
 * to the exception. This can be used to customize the error message displayed to 
 * the user. The meaning of these codes is determined by the application owner.
 * 
 * For example, the app owner may determine that the codes mean the following:
 * 
 * 1: Scheduled maintenance
 * 2: Technical difficulties
 * 
 * The `/bin/error_pages/spitfire/mvc/middleware/exceptions/MaintenanceMode/` directory
 * could contain error documents `1.php` and `2.php` to provide information according
 * to the state of the application.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class MaintenanceMiddleware implements MiddlewareInterface
{
	
	public function after(ContextInterface $context, Response $response = null) {
		//Do nothing, after the request was processed, this can just continue.
	}

	public function before(ContextInterface $context) {
		if (Environment::get('spitfire.maintenance')) {
			throw new MaintenanceModeException('Maintenance mode is enabled', Environment::get('spitfire.maintenance'));
		}
	}

}