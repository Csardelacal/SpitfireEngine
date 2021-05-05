<?php namespace spitfire\io\template;

use Closure;
use spitfire\exceptions\PrivateException;
use spitfire\exceptions\FileNotFoundException;

/* 
 * The MIT License
 *
 * Copyright 2018 César de la Cal Bretschneider <cesar@magic3w.com>.
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
  * @todo Implement template inheritance mechanisms.
  */
class Template
{
	
	private $file;
	private $data = [];
	
	/**
	 * If this template extends another, the behavior changes slightly. Sections will not
	 * be rendered, and instead they'll be pushed to the extended parent to render it's
	 * layout.
	 * 
	 * @var Template|null
	 */
	private $extends = null;
	
	/**
	 * 
	 * @var Closure[]
	 */
	private $sections;
	
	public function __construct($file) 
	{
		$this->file = $file;
	}
	
	public function setFile($file) {
		$this->file = $file;
		return $this;
	}
	
	public function extends($template) 
	{
		$this->extends = new Template($template);
		return $this;
	}
	
	/**
	 * This method allows a child template to pass all of the blocks it has
	 * available to the view it's extending.
	 * 
	 * @param Closure[] $sections
	 */
	public function setSections(array $sections) 
	{
		$this->sections = $sections;
		return $this;
	}
	
	/**
	 * 
	 */
	public function section($name, Closure $content = null) 
	{
		/**
		 * If the section is being set, and the section has not yet been overriden
		 * by a template that was executed before this one, we can override it.
		 */
		if ($content && !isset($this->sections[$name])) {
			$this->sections[$name] = $content;
		}
		
		if (isset($this->sections[$name]) && !$this->extends) {
			$this->sections[$name]($this);
		}
		
		return $this;
	}
	
	/**
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return Template
	 */
	public function set(string $key, $value) : Template
	{
		$this->data[$key] = $value;
		return $this;
	}
	
	public function render() 
	{	
		ob_start();
		
		foreach ($this->data as $__var => $__content) {
			$$__var = $__content;
		}
		
		include $this->file . '.php';
		
		if ($this->extends) {
			ob_clean();
			$this->extends
				->setSections($this->sections)
				->render();
		}
		
		return ob_get_clean();
	}
	
}
