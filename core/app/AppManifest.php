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
 * @todo Add an option to add providers
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
	 * @param Collection<AppManifest> $apps
	 * @param array $events
	 */
	public function __construct(string $name, Collection $apps, array $events) 
	{
		$this->name = $name;
		$this->apps = $apps;
		$this->events = $events;
	}
	
	public function getName(): string {
		return $this->name;
	}
	
	public function getApps(): mixed {
		return $this->apps;
	}
	
	public function getEvents(): array {
		return $this->events;
	}
	
}
