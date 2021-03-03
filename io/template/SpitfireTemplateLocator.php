<?php namespace spitfire\io\template;

use Throwable;
use spitfire\utils\Strings;
use ReflectionClass;
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

/**
 * The template locator provides functionality to locate a template for a specific
 * use case. This can range anywhere from:
 * 
 * - Layouts
 * - Templates
 * - Components
 * 
 * Unlike previous iterations, this component has become very static about where
 * it looks for a file, if it's not there, it won't be retrieved.
 */
class SpitfireTemplateLocator implements TemplateLocatorInterface
{
	
	/**
	 * Defines the directory where view resources are located, this will be under
	 * `$resources/views`.
	 *
	 * @var string
	 */
	private $basedir;
	
	public function __construct() {
		$this->basedir = spitfire()->locations()->resources('views/');
	}
	
	/**
	 * Returns the file that should be used to render a component.
	 * 
	 * @param string $identifier
	 * @return string[]
	 */
	public function component($identifier) {
		return [ "{$this->basedir}components/{$identifier}.php" ];
	}
	
	/**
	 * Returns a list of candidate files that could be used to render a error page
	 * for a certain exception. This uses reflection to generate a list of pages
	 * that for the types the error inherits from.
	 * 
	 * This means that a LoginRequired exception that inherits from PublicException
	 * will generate a list of candidates like this
	 * 
	 * - views/error/myapp/LoginRequiredException/{code}{.responseType}.php
	 * - views/error/spitfire/exception/PublicException/{code}{.responseType}.php
	 * 
	 * @param string $type
	 * @param string $extension
	 * @return string[]
	 */
	public function exception($type, $extension) {
		
		$reflection = new ReflectionClass($type);
		$candidates = collect();
		$basedir = $this->basedir;
		
		/*
		 * For each type this error inherits from, we will add the appropriate
		 * templates to render it's parent.
		 */
		while ($reflection) {
			$fqn = str_replace('\\', '/', $reflection->getName());

			$candidates->add([
				"{$basedir}error/{$fqn}/{$e->getCode()}{$extension}.php",
				"{$basedir}error/{$fqn}/default{$extension}.php",
			]);

			$reflection = $reflection->getParentClass();
		}
		
		/*
		 * A few fallback error pages in case the exception was not handled by the
		 * system the way it should, preventing it from escaping.
		 */
		$candidates->add([
			 "{$basedir}error/{$e->getCode()}{$extension}.php",
			 "{$basedir}error/default{$extension}.php",
		]);
			 
		return $candidates->toArray();
	}
	
	/**
	 * Returns a candidate template file for the template to be loaded. This has 
	 * become much stricter and we now require that a call to a certain template
	 * issues a unique locator.
	 * 
	 * @param string $template
	 * @param string $extension
	 * @return string[]
	 */
	public function template($template, $extension) 
	{
		return [ "{$this->basedir}{$template}{$extension}.php" ];
	}
	
	/**
	 * Returns the filename for the layout to be used. The user can specify a layout
	 * to be used.
	 * 
	 * @param string $disambiguation
	 * @param string $extension
	 * @return string[]
	 */
	public function layout($disambiguation = '', $extension = '')
	{	
		$name = $disambiguation?: 'layout';
		return [ "{$this->basedir}layout/{$name}{$extension}.php" ];
	}

}
