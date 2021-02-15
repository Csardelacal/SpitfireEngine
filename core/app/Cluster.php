<?php namespace spitfire\core\app;

use spitfire\App;
use spitfire\collection\Collection;

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
 * A cluster is a collection of applications that this spitfire instance is managing.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Cluster
{
	
	/**
	 * Contains the underlying collection of applications that the cluster is 
	 * managing.
	 *
	 * @var Collection
	 */
	private $apps;
	
	/**
	 * Instances a new cluster. The cluster allows the application access to the 
	 * apps that this spitfire instance is managing.
	 * 
	 * Apps are referenced by their name (often still referenced as URL space, since
	 * they are often equivalent) and need to be unique.
	 */
	public function __construct()
	{
		$this->apps = new Collection();
	}
	
	/**
	 * Add an application to the cluster
	 * 
	 * @param App $app
	 */
	public function put(App $app)
	{
		$this->apps[$app->url()] = $app;
	}
	
	/**
	 * Returns the selected app from spitfire.
	 * 
	 * @param string $name
	 * @return App
	 * @throws AppNotFoundException
	 */
	public function get($name) 
	{
		/*
		 * If we do not have a app registered for this cluster, we throw an exception.
		 */
		if (!$this->apps->has($name)) {
			throw new AppNotFoundException(sprintf('No app registered for namespace %s', $name));
		}
		
		return $this->apps[$name];
	}
	
	/**
	 * Returns whether there is already an application registered with the cluster.
	 * 
	 * @param string $name
	 * @return bool
	 */
	public function has($name)
	{
		return $this->apps->has($name);
	}
	
	/**
	 * This is a weird scenario, it's rarely useful to remove an application you
	 * just loaded from the cluster. This is mostly used to implement safe-mode
	 * style operations.
	 * 
	 * @param string $name
	 * @return void
	 * @throws AppNotFoundException
	 */
	public function remove($name) : void
	{
		/*
		 * If we do not have a app registered for this cluster, we throw an exception.
		 */
		if (!$this->apps->has($name)) {
			throw new AppNotFoundException(sprintf('No app registered for namespace %s', $name));
		}
		
		$this->apps->offsetUnset($name);
	}
}
