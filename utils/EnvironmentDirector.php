<?php namespace spitfire\utils;

use spitfire\core\Environment;
use spitfire\mvc\Director;

/* 
 * The MIT License
 *
 * Copyright 2019 César de la Cal Bretschneider <cesar@magic3w.com>.
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

class EnvironmentDirector extends Director
{
	
	public function set($set, $value) {
		$env = Environment::get();
		$keys = $env->keys();
		
		echo '<?php', PHP_EOL;
		echo PHP_EOL;
		echo '$e = new ' . Environment::class . '("deploy");', PHP_EOL;
		echo PHP_EOL;
		
		foreach ($keys as $key) {
			if ($key === $set) { $val = $value; }
			else { $val = $env->get($key); }
			
			if (is_array($val)) {
				$str = implode(', ', array_map(function ($e) { return "'". addslashes($e) . "'"; }, $val));
				echo sprintf('$e->set(\'%s\', [%s]);', addslashes($key), $str);
			}
			else {
				echo sprintf('$e->set(\'%s\', \'%s\');', addslashes($key), addslashes($val));
			}
			
			echo PHP_EOL;
		}
		
		if (!in_array($set, $keys)) {
			echo sprintf('$e->set(\'%s\', \'%s\');', addslashes($set), addslashes($value));
			echo PHP_EOL;
		}
		
		echo PHP_EOL;
		
	}
	
	public function get($key) {
		echo Environment::get($key);
	}
	
	public function dump() {
		$env = Environment::get();
		var_dump($env);
	}
}