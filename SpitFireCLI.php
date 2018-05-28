<?php namespace spitfire;

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

class SpitFireCLI extends SpitFire
{
	
	public function fire() {
		if (php_sapi_name() !== 'cli') {
			throw new PublicException('Invalid request', 400);
		}
		
		#Import the apps
		include CONFIG_DIRECTORY . 'apps.php';
		include CONFIG_DIRECTORY . 'middleware.php';
		
		#Get the parameters from the command line interface
		$parser = new io\cli\arguments\Parser();
		$args   = $parser->read($argv);
		
		#The first two arguments are gonna be the director and action
		$director = $args->arguments()->shift();
		$action   = $args->arguments()->shift();
		
		$context  = core\ContextCLI::create(str_replace('.', '\\', $director) . 'Director', $action, $args);
		$context->run();
		
	}
	
}
