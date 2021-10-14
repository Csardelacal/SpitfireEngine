<?php namespace spitfire\core\service;

use spitfire\provider\Container;

/* 
 * The MIT License
 *
 * Copyright 2021 César de la Cal Bretschneider <cesar@magic3w.com>.
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
 * A provider simply provides two methods that allow the application to register
 * services with the container, and to initialize the services after they have 
 * been loaded.
 * 
 * The register method allows a service provider to load all the services it needs
 * onto the container, the init method then allows to initialize the services needed
 * for the application knowing that all services are enabled.
 */
abstract class Provider
{
	
	/**
	 *
	 * @var Container
	 */
	protected $container;
	
	public function __construct(Container $container) {
		$this->$container = $container;
	}
	
	/**
	 * Implementations of this function must only register services. Performing
	 * other operations may lead to unexpected behavior.
	 * 
	 */
	abstract public function register();
	
	/**
	 * Use this to initialize your services, register published resources etc.
	 * 
	 */
	abstract public function init();
	
}
