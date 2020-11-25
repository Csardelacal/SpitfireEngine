<?php namespace spitfire\io\template;

use Throwable;
use spitfire\utils\Strings;
use function collect;
use function spitfire;

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

class SpitfireTemplateLocator implements TemplateLocatorInterface
{
	
	private $root;
	private $basedir;
	
	public function __construct($basedir) {
		#TODO: This should be replaced with a proper location, the cwd is not the app root
		$this->root  = spitfire()->getCWD();
		$this->basedir = rtrim($basedir, '\/') . DIRECTORY_SEPARATOR;
		
		if (Strings::startsWith($this->basedir, $this->root)) {
			$this->basedir = substr($this->basedir, strlen($this->root));
		}
	}
	
	public function element($identifier) {
		return [
			"override/{$this->basedir}elements/{$identifier}",
			"override/{$this->basedir}elements/{$identifier}.php",
			"{$this->basedir}elements/{$identifier}",
			"{$this->basedir}elements/{$identifier}.php"
		];
	}
	
	public function exception(Throwable $e, $extension) 
	{	
		$reflection = new \ReflectionClass(get_class($e));
		$candidates = collect();
		$basedir = $this->basedir;

		while ($reflection) {
			$fqn = str_replace('\\', '/', $reflection->getName());

			$candidates->add([
				"override/{$basedir}/bin/error_pages/{$fqn}/{$e->getCode()}{$extension}.php",
				"override/{$basedir}/bin/error_pages/{$fqn}/default{$extension}.php",
				"override/{$basedir}/bin/error_pages/{$fqn}/{$e->getCode()}.php",
				"override/{$basedir}/bin/error_pages/{$fqn}/default.php",
				"{$basedir}/bin/error_pages/{$fqn}/{$e->getCode()}{$extension}.php",
				"{$basedir}/bin/error_pages/{$fqn}/default{$extension}.php",
				"{$basedir}/bin/error_pages/{$fqn}/{$e->getCode()}.php",
				"{$basedir}/bin/error_pages/{$fqn}/default.php"
			]);

			$reflection = $reflection->getParentClass();
		}

		$candidates->add([
			 "override/{$basedir}/bin/error_pages/{$e->getCode()}{$extension}.php",
			 "override/{$basedir}/bin/error_pages/default{$extension}.php",
			 "override/{$basedir}/bin/error_pages/{$e->getCode()}.php",
			 "override/{$basedir}/bin/error_pages/default.php",
			 "{$basedir}/bin/error_pages/{$e->getCode()}{$extension}.php",
			 "{$basedir}/bin/error_pages/default{$extension}.php",
			 "{$basedir}/bin/error_pages/{$e->getCode()}.php",
			 "{$basedir}/bin/error_pages/default.php"
		]);
			 
		return $candidates->toArray();
	}
	
	public function template($controllerURI, $action, $extension) {
		
		$controller = strtolower(implode(DIRECTORY_SEPARATOR, $controllerURI));
		
		return [
			"override/{$this->basedir}{$controller}/{$action}{$extension}.php",
			"override/{$this->basedir}{$controller}{$extension}.php",
			"{$this->basedir}{$controller}/{$action}{$extension}.php",
			"{$this->basedir}{$controller}{$extension}.php",
		];
	}

	public function layout($controllerURI = null){
		
		$controller = strtolower(implode(DIRECTORY_SEPARATOR, $controllerURI));
		
		return [
			"override/{$this->basedir}{$controller}/layout.php",
			"override/{$this->basedir}layout.php",
			"{$this->basedir}{$controller}/layout.php",
			"{$this->basedir}layout.php"
		];
	}

}