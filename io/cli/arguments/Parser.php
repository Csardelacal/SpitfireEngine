<?php namespace spitfire\io\cli\arguments;

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

class Parser
{
	
	private $script;
	private $parameters;
	private $arguments;
	
	private $aliases;
	
	public function __construct($argv, $aliases = []) {
		$script     = array_shift($argv);
		$parameters = [];
		$arguments  = [];
		$stop       = false;
		
		foreach ($argv as $arg) {
			/**/if ($arg === '--') {
				$stop = true;
			}
			elseif (\Strings::startsWith($arg, '--') && !$stop) { 
				list($name, $value) = explode('=', $arg, 2);
				$name  = substr($name, 2);
				$value = $value? $value : true;
				$parameters[$name] = $value;
			}
			elseif ($arg === '-') {
				$read = [STDIN];
				$write = [];
				$except = [];
				if (stream_select($read, $write, $except, 0)) {
					$arguments[] = file_get_contents('php://stdin');
				}
				else {
					$arguments[] = null;
				}
			}
			elseif (\Strings::startsWith($arg, '-' ) && !$stop) {
				list($name, $value) = explode('=', $arg, 2);
				$name  = str_split(substr($name, 1), 1);
				$value = $value? $value : true;
				
				$first = array_pop($name);
				$parameters[isset($aliases[$first])? $aliases[$first] : $first] = $value;
				
				foreach ($name as $flag) { 
					$flag = isset($aliases[$flag])? $aliases[$flag] : $flag;
					$parameters[$flag] = isset($parameters[$flag])? $parameters[$flag] + 1 : 1; 
				}
			}
			else { 
				$arguments[] = $arg;
			}
			
		}
		
		$this->aliases = $aliases;
		$this->script = $script;
		$this->arguments = $arguments;
		$this->parameters = $parameters;
	}
	
	public function getScript() {
		return $this->script;
	}
	
	public function getParameters() {
		return $this->parameters;
	}
	
	public function getArguments() {
		return $this->arguments;
	}
	
}
