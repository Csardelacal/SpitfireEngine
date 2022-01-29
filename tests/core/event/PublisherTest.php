<?php namespace tests\spitfire\core\event;

use PHPUnit\Framework\TestCase;
use spitfire\core\event\Event;
use spitfire\core\event\EventDispatcher;
use function collect;

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

class PublisherTest extends TestCase
{
	
	private $plugins;
	
	public function setUp() : void
	{
		$this->plugins = new EventDispatcher();
		
		$this->plugins->on('test')->do(function (Event$e) { 
			return $e->getBody()->push(1);
		});
		
		$this->plugins->on('test.n1.before')->do(function (Event$e) { 
			return $e->getBody()->push(1);
		});
		
		parent::setUp();
	}
	
	public function testTarget()
	{
		$payload = collect();
		
		$this->plugins->dispatch(new Event('test', $payload));
		$this->assertEquals(1, $payload->count());
		
		$this->plugins->dispatch(new Event('test.n1.before', $payload));
		$this->assertEquals(2, $payload->count());
	}
}
