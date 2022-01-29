<?php namespace spitfire\core\resource;

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
 * The publisher provides the application with the ability to register files that
 * should get merged into the main repository of the application so that resources
 * can be :
 * 
 * A) Overwritten in a package that extends the application. Something like "pete's
 * forum" could require "vendor/forumsoftware" using composer, and then use the
 * publish method to import the forum's templates into the main repository, allowing
 * them to modify the templates to their liking, commit, and push it to a server.
 * 
 * B) Overwritten by a package that extends the software. A theme (for example)
 * that is installed as a dependency of the software, can overwrite the templates
 * of the software.
 * 
 * There may be a mix of both, where the user installs a package that overwrites
 * the resource of the application, and then goes ahead and overrides them again.
 * 
 * @author César de la Cal Bretschneider <cesar@magic3w.com>
 */
class Publisher
{
	
	/**
	 * This array contains a list of files that the application should publish
	 * when the publish director is invoked. Publishing means that spitfire copies
	 * the sources from a package that has been imported to the top-level repository,
	 * allowing packages to potentially extend / override functionality provided
	 * by the base application.
	 *
	 * @var string[][][]
	 */
	private $publishes = [];
	
	
	/**
	 * The publish method allows the application to register a file that should
	 * be written to a different location whenever the developer invokes the
	 * spitfire.publish director.
	 * 
	 * @param string $tag
	 * @param string $from
	 * @param string $to
	 */
	public function publish(string $tag, string $from, string $to) 
	{
		if (!array_key_exists($tag, $this->publishes)) {
			$this->publishes[$tag] = []; 
		}
		$this->publishes[$tag][] = [$from, $to];
	}
	
	/**
	 * Returns the publications made under a certain tag. Also, note that sources 
	 * and targets may be directories, even mismatched ones.
	 * 
	 * The return format is an array that looks like this:
	 * [
	 *   [from, to],
	 *   [from, to],
	 *   [from, to]
	 * ]
	 * 
	 * @param string $tag
	 * @return string[][]
	 */
	public function get(string $tag) 
	{
		return $this->publishes[$tag];
	}
	
	/**
	 * Returns a list of tags available to publish. This allows the application
	 * to assemble a list of tags available to publish, or to iterate over them
	 * and build them all.
	 * 
	 * @return string[]
	 */
	public function tags()
	{
		return array_keys($this->publishes);
	}
}
