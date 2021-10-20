<?php namespace spitfire\core\app;

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
 * The app manifest object contains information that the user can define by adding 
 * a manifest file to the project. Currently, the only way to add manifest information
 * to a project is through composer.json
 * 
 * Please note that the application may cache the manifest object in production
 * environments, making it ignore changes to the file itself. To rebuild the cache,
 * please execute the appropriate command.
 * 
 * @todo Add an option to add publishing
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class AppManifest
{
	
	/**
	 * The name of the application. This should only be used for reference and
	 * debugging and does not reflect any real content.
	 *
	 * @var string
	 */
	private $name;
	
	/**
	 * The entrypoint is the name of the class that initializes the application,
	 * this allows the application to intiailize it's routes.
	 * 
	 * @var class-string
	 */
	private $entrypoint;
	
	/**
	 * An array of applications that this package provides. These may request 
	 * Spitfire to load apps from other manifests. This also defines which events
	 * these applications receive
	 *
	 * @var mixed
	 */
	private $apps;
	
	/**
	 * Contains an array of events that the system is listening for. Please note
	 * that the system requires a two way connection, the apps entry needs to broadcast
	 * the events to this
	 *
	 * @var string[]
	 */
	private $events;
	
	/**
	 * The manifest object contains the data that spitfire could extract and use
	 * from an app manifest included in composer.json
	 * 
	 * @param string $name
	 * @param string $entrypoint
	 * @param Collection<AppManifest> $apps
	 * @param string[] $events
	 */
	public function __construct(string $name, string $entrypoint, Collection $apps, array $events) 
	{
		assert(class_exists($entrypoint));
		
		$this->name = $name;
		$this->apps = $apps;
		$this->events = $events;
		$this->entrypoint = $entrypoint;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	/**
	 * Returns the name of the class that can be used to bootstrap this application, this
	 * includes the init code for the router, allows the application to initialize itself,
	 * etc.
	 * 
	 * @return class-string
	 */
	public function getEntrypoint(): string 
	{
		assert(class_exists($this->entrypoint));
		return $this->entrypoint;
	}
	
	public function getApps(): mixed {
		return $this->apps;
	}
	
	/**
	 * 
	 * @return string[]
	 */
	public function getEvents(): array {
		return $this->events;
	}
	
}
